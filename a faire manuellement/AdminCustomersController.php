<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminCustomersControllerCore extends AdminController
{
	protected $delete_mode;

	protected $_defaultOrderBy = 'date_add';
	protected $_defaultOrderWay = 'DESC';
	protected $can_add_customer = true;

	public function __construct()
	{
		$this->bootstrap = true;
		$this->required_database = true;
		$this->required_fields = array('newsletter','optin');
		$this->table = 'customer';
		$this->className = 'Customer';
		$this->lang = false;
		$this->deleted = true;
		$this->explicitSelect = true;

		$this->allow_export = false;

		$this->addRowAction('edit');
		$this->addRowAction('view');
		$this->addRowAction('delete');
		$this->bulk_actions = array(
			'delete' => array(
				'text' => $this->l('Delete selected'),
				'confirm' => $this->l('Delete selected items?'),
				'icon' => 'icon-trash'
			)
		);

		$this->context = Context::getContext();

		$this->default_form_language = $this->context->language->id;

		$titles_array = array();
		$genders = Gender::getGenders($this->context->language->id);
		foreach ($genders as $gender)
			$titles_array[$gender->id_gender] = $gender->name;

		$this->_select = '
		a.date_add, gl.name as title, (
			SELECT SUM(total_paid_real / conversion_rate)
			FROM '._DB_PREFIX_.'orders o
			WHERE o.id_customer = a.id_customer
			'.Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o').'
			AND o.valid = 1
		) as total_spent, (
			SELECT c.date_add FROM '._DB_PREFIX_.'guest g
			LEFT JOIN '._DB_PREFIX_.'connections c ON c.id_guest = g.id_guest
			WHERE g.id_customer = a.id_customer
			ORDER BY c.date_add DESC
			LIMIT 1
		) as connect, (SELECT CONCAT(b.phone, ":", b.phone_mobile) as phone FROM '._DB_PREFIX_.'address b WHERE b.id_customer = a.id_customer LIMIT 1) as phone
		'; // added
		$this->_join = 'LEFT JOIN '._DB_PREFIX_.'gender_lang gl ON (a.id_gender = gl.id_gender AND gl.id_lang = '.(int)$this->context->language->id.')';
		$this->fields_list = array(
			'id_customer' => array(
				'title' => $this->l('ID'),
				'align' => 'text-center',
				'class' => 'fixed-width-xs'
			),
			'title' => array(
				'title' => $this->l('Social title'),
				'filter_key' => 'a!id_gender',
				'type' => 'select',
				'list' => $titles_array,
				'filter_type' => 'int',
				'order_key' => 'gl!name'
			),
			'lastname' => array(
				'title' => $this->l('Last name')
			),
			'firstname' => array(
				'title' => $this->l('First name')
			),
			'email' => array(
				'title' => $this->l('Email address')
			),
		);

		if (Configuration::get('PS_B2B_ENABLE'))
		{
			$this->fields_list = array_merge($this->fields_list, array(
				'company' => array(
					'title' => $this->l('Company')
				),
			));
		}

		$this->fields_list = array_merge($this->fields_list, array(

		'phone' => array(
				'title' => $this->l('Tel'),
				'align' => 'text-center',
				'type' => 'text',
				'orderby' => false,
				'havingFilter' => true,
				'callback' => 'makePhoneCall' // Keyyo
			),

			'total_spent' => array(
				'title' => $this->l('Sales'),
				'type' => 'price',
				'search' => false,
				'havingFilter' => true,
				'align' => 'text-right',
				'badge_success' => true
			),


			'active' => array(
				'title' => $this->l('Enabled'),
				'align' => 'text-center',
				'active' => 'status',
				'type' => 'bool',
				'orderby' => false,
				'filter_key' => 'a!active'
			),
			'newsletter' => array(
				'title' => $this->l('Newsletter'),
				'align' => 'text-center',
				'type' => 'bool',
				'callback' => 'printNewsIcon',
				'orderby' => false
			),
			'optin' => array(
				'title' => $this->l('Opt-in'),
				'align' => 'text-center',
				'type' => 'bool',
				'callback' => 'printOptinIcon',
				'orderby' => false
			),
			'date_add' => array(
				'title' => $this->l('Registration'),
				'type' => 'date',
				'align' => 'text-right'
			),
			'connect' => array(
				'title' => $this->l('Last visit'),
				'type' => 'datetime',
				'search' => false,
				'havingFilter' => true
			)
		));

		$this->shopLinkType = 'shop';
		$this->shopShareDatas = Shop::SHARE_CUSTOMER;

		parent::__construct();

        // Ajout pour les appels AJAX du module KEYYO
        $this->addJquery();
        $this->addJS(_PS_MODULE_DIR_ . 'keyyo/views/js/adminkeyyo.js');
        $this->addCSS(_PS_MODULE_DIR_ . 'keyyo/views/css/adminkeyyo.css');

		// Check if we can add a customer
		if (Shop::isFeatureActive() && (Shop::getContext() == Shop::CONTEXT_ALL || Shop::getContext() == Shop::CONTEXT_GROUP))
			$this->can_add_customer = false;
	}

	public function postProcess()
	{
		if (!$this->can_add_customer && $this->display == 'add')
			$this->redirect_after = $this->context->link->getAdminLink('AdminCustomers');

		$customer = new Customer(Tools::getValue('id_customer'));

		// added sending message / get reference order info
		/*$result = Db::getInstance()->executeS('
		SELECT id_order
		FROM '._DB_PREFIX_.'orders
		WHERE id_customer = '.(int)$customer->id.' ORDER BY date_add');
		$orders = array();

		foreach ($result as $row)
		{
			$order = new Order($row['id_order']);

			$date = explode(' ', $order->date_add);
			$orders[] = array('value' => $order->id, 'label' => $order->getUniqReference().' - '.Tools::displayDate($date[0], null) );
		}


		$this->context->smarty->assign('orderList', $orders);	*/
		$this->context->smarty->assign('orderMessages', OrderMessage::getOrderMessages((int)$this->context->language->id));
		// end added sending message

		// added for sending message to customer
		if (Tools::isSubmit('submitMessage'))
		{
				$extension = array('.txt', '.rtf', '.doc', '.docx', '.pdf', '.zip', '.png', '.jpeg', '.gif', '.jpg','.xls','.xlsx');
				$file_attachment = Tools::fileAttachment('fileUpload');

				$customer = new Customer(Tools::getValue('id_customer'));
				if (!Validate::isLoadedObject($customer))
					$this->errors[] = Tools::displayError('The customer is invalid.');
				elseif (!Tools::getValue('message'))
					$this->errors[] = Tools::displayError('The message cannot be blank.');
				elseif (!empty($file_attachment['name']) && $file_attachment['error'] != 0)
					$this->errors[] = Tools::displayError('An error occurred during the file-upload process.');
				elseif (!empty($file_attachment['name']) && !in_array(Tools::strtolower(substr($file_attachment['name'], -4)), $extension) && !in_array(Tools::strtolower(substr($file_attachment['name'], -5)), $extension))
					$this->errors[] = Tools::displayError('Bad file extension');
				else
				{


					$rules = call_user_func(array('Message', 'getValidationRules'), 'Message');
					foreach ($rules['required'] as $field)
						if (($value = Tools::getValue($field)) == false && (string)$value != '0')
							if (!Tools::getValue('id_'.$this->table) || $field != 'passwd')
								$this->errors[] = sprintf(Tools::displayError('field %s is required.'), $field);
					foreach ($rules['size'] as $field => $maxLength)
						if (Tools::getValue($field) && Tools::strlen(Tools::getValue($field)) > $maxLength)
							$this->errors[] = sprintf(Tools::displayError('field %1$s is too long (%2$d chars max).'), $field, $maxLength);
					foreach ($rules['validate'] as $field => $function)
						if (Tools::getValue($field))
							if (!Validate::$function(htmlentities(Tools::getValue($field), ENT_COMPAT, 'UTF-8')))
								$this->errors[] = sprintf(Tools::displayError('field %s is invalid.'), $field);

					if (!count($this->errors))
					{

					//	$order_id_customer=Tools::getValue('id_order');

						$id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, /*$order_id_customer*/ 0);

							if (!$id_customer_thread)
							{
								$customer_thread = new CustomerThread();
								$customer_thread->id_contact = 0;
								$customer_thread->id_customer = (int)Tools::getValue('id_customer');
								$customer_thread->id_shop = (int)$this->context->shop->id;
						//		$customer_thread->id_order = (int)$order_id_customer;
								$customer_thread->id_lang = (int)$this->context->language->id;
								$customer_thread->email = $customer->email;
								$customer_thread->status = 'open';
								$customer_thread->token = Tools::passwdGen(12);
								$customer_thread->add();
							}
							else
							{
								$customer_thread = new CustomerThread((int)$id_customer_thread);
							}

							$customer_message = new CustomerMessage();
							$customer_message->id_customer_thread = $customer_thread->id;
							$customer_message->id_employee = (int)$this->context->employee->id;
							$customer_message->message = Tools::getValue('message');
							$customer_message->private = 0;
							$customer_message->file_name = $file_attachment['rename'];

							if (!$customer_message->add())
								$this->errors[] = Tools::displayError('An error occurred while saving the message.');
							elseif ($customer_message->private)
							{

									Tools::redirectAdmin(self::$currentIndex.'&id_customer='.$customer->id.'&&viewcustomer&token='.$this->token);
							}
							else
							{

								$message = Tools::getValue('message');;
								/*if (Configuration::get('PS_MAIL_TYPE', null, null, $order->id_shop) != Mail::TYPE_TEXT)
									$message = Tools::nl2br($customer_message->message);*/

								$varsTpl = array(
									'{lastname}' => $customer->lastname,
									'{firstname}' => $customer->firstname,
									'{attached_file}' => '-',
									/*'{id_order}' => $order->id,*/
									'{message}' => $message
								);

								if (isset($file_attachment['rename']) && !empty($file_attachment['rename']) && rename($file_attachment['tmp_name'], _PS_UPLOAD_DIR_.basename($file_attachment['rename'])))
								{
									@chmod(_PS_UPLOAD_DIR_.basename($file_attachment['rename']), 0664);
								}


								if (isset($file_attachment['name']))
								$varsTpl['{attached_file}'] = $file_attachment['name'];

								// @
	/*Mail::Send((int)Context::getContext()->language->id, 'customer_message', Mail::l('New message', (int)Context::getContext()->language->id),
							$varsTpl, $customer->email, $customer->firstname, null, null,
									$file_attachment);*/

								if (@Mail::Send((int)Context::getContext()->language->id, 'customer_message',
									Mail::l('New message', (int)Context::getContext()->language->id), $varsTpl, $customer->email,
									$customer->firstname.' '.$customer->lastname, null, null, $file_attachment, null, _PS_MAIL_DIR_, true, (int)Context::getContext()->shop->id))
								{

									Tools::redirectAdmin(self::$currentIndex.'&id_customer='.$customer->id.'&viewcustomer&send_message=1&token='.$this->token);
								}
								else
								{
									$this->errors[] = Tools::displayError('An error occurred while sending an email to the customer.');
								}
							 }
					}

				}


		}



		// end added for sending message to customer

		parent::postProcess();
	}

	public function initContent()
	{
		if ($this->action == 'select_delete')
			$this->context->smarty->assign(array(
				'delete_form' => true,
				'url_delete' => htmlentities($_SERVER['REQUEST_URI']),
				'boxes' => $this->boxes,
			));

		if (!$this->can_add_customer && !$this->display)
			$this->informations[] = $this->l('You have to select a shop if you want to create a customer.');

		// Vérification du numéro d'appel employé pour KEYYO
        $keyyo_caller = $this->context->employee->getKeyyoCaller();
        if (!$keyyo_caller) {
            $this->errors[] = 'Veuillez configurer votre numéro d\'appel dans l\'onglet Administration > Employés ';
        }

		parent::initContent();
	}

	public function initToolbar()
	{
		parent::initToolbar();
		if (!$this->can_add_customer)
			unset($this->toolbar_btn['new']);
		elseif (!$this->display) //display import button only on listing
		{
			$this->toolbar_btn['import'] = array(
				'href' => $this->context->link->getAdminLink('AdminImport', true).'&import_type=customers',
				'desc' => $this->l('Import')
			);
		}
	}

	public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
	{
		parent::getList($id_lang, $orderBy, $orderWay, $start, $limit, $id_lang_shop);

		if ($this->_list)
			foreach ($this->_list as &$row)
				$row['badge_success'] = $row['total_spent'] > 0;
	}


	public function initToolbarTitle()
	{
		parent::initToolbarTitle();

		switch ($this->display)
		{
			case '':
			case 'list':
				array_pop($this->toolbar_title);
				$this->toolbar_title[] = $this->l('Manage your Customers');
				break;
			case 'view':
				if (($customer = $this->loadObject(true)) && Validate::isLoadedObject($customer))
					array_pop($this->toolbar_title);
					$this->toolbar_title[] = sprintf('Information à propos du Client : %s', Tools::substr($customer->firstname).'. '.$customer->lastname);
				break;
			case 'add':
			case 'edit':
				array_pop($this->toolbar_title);
				if (($customer = $this->loadObject(true)) && Validate::isLoadedObject($customer))
					$this->toolbar_title[] = sprintf($this->l('Editing Customer: %s'), Tools::substr($customer->firstname).'. '.$customer->lastname);
				else
					$this->toolbar_title[] = $this->l('Creating a new Customer');
				break;
		}

		array_pop($this->meta_title);
		if (count($this->toolbar_title) > 0)
			$this->addMetaTitle($this->toolbar_title[count($this->toolbar_title) - 1]);
	}

	public function initPageHeaderToolbar()
	{
		if (empty($this->display) && $this->can_add_customer)
			$this->page_header_toolbar_btn['new_customer'] = array(
				'href' => self::$currentIndex.'&addcustomer&token='.$this->token,
				'desc' => $this->l('Add new customer', null, null, false),
				'icon' => 'process-icon-new'
			);

		parent::initPageHeaderToolbar();
	}

	public function initProcess()
	{
		parent::initProcess();

		if (Tools::isSubmit('submitGuestToCustomer') && $this->id_object)
		{
			if ($this->tabAccess['edit'] === '1')
				$this->action = 'guest_to_customer';
			else
				$this->errors[] = Tools::displayError('You do not have permission to edit this.');
		}
		elseif (Tools::isSubmit('changeNewsletterVal') && $this->id_object)
		{
			if ($this->tabAccess['edit'] === '1')
				$this->action = 'change_newsletter_val';
			else
				$this->errors[] = Tools::displayError('You do not have permission to edit this.');
		}
		elseif (Tools::isSubmit('changeOptinVal') && $this->id_object)
		{
			if ($this->tabAccess['edit'] === '1')
				$this->action = 'change_optin_val';
			else
				$this->errors[] = Tools::displayError('You do not have permission to edit this.');
		}

		// When deleting, first display a form to select the type of deletion
		if ($this->action == 'delete' || $this->action == 'bulkdelete')
			if (Tools::getValue('deleteMode') == 'real' || Tools::getValue('deleteMode') == 'deleted')
				$this->delete_mode = Tools::getValue('deleteMode');
			else
				$this->action = 'select_delete';
	}

	public function renderList()
	{
		if ((Tools::isSubmit('submitBulkdelete'.$this->table) || Tools::isSubmit('delete'.$this->table)) && $this->tabAccess['delete'] === '1')
			$this->tpl_list_vars = array(
				'delete_customer' => true,
				'REQUEST_URI' => $_SERVER['REQUEST_URI'],
				'POST' => $_POST
			);

		return parent::renderList();
	}

	public function renderForm()
	{
		if (!($obj = $this->loadObject(true)))
			return;

		$genders = Gender::getGenders();
		$list_genders = array();
		foreach ($genders as $key => $gender)
		{
			$list_genders[$key]['id'] = 'gender_'.$gender->id;
			$list_genders[$key]['value'] = $gender->id;
			$list_genders[$key]['label'] = $gender->name;
		}

		$years = Tools::dateYears();
		$months = Tools::dateMonths();
		$days = Tools::dateDays();

		$groups = Group::getGroups($this->default_form_language, true);

		// added

		$options = array();

		$options[] = array(
			"id" => '',
			"name" => ''
		  );

		foreach (Customer::getCustomers() as $row)
		{
		  $options[] = array(
			"id" => (int)$row['id_customer'],
			"name" => (int)$row['id_customer'].' ('.$row['lastname'].' '.$row['firstname'].' )',
		  );
		}

		$options[] = array(
			"id" => '',
			"name" => ''
		  );
		// end added
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->l('Customer'),
				'icon' => 'icon-user'
			),
			'input' => array(

				array(
				  'type' => 'text',                              // This is a <select> tag.
				  'label' => $this->l('Parrain:'),         // The <label> for this <select> tag.
				  'desc' => $this->l('Choisir un parain'),  // A help text, displayed right next to the <select> tag.
				  'name' => 'id_sponsor',                     // The content of the 'id' attribute of the <select> tag.
				  'id' => 'id_sponsor',                     // The content of the 'id' attribute of the <select> tag.
				  'class' => 'id_sponsor',                     // The content of the 'id' attribute of the <select> tag.
				  'required' => true,                              // If set to true, this option must be set.

				),
				array(
					'type' => 'radio',
					'label' => $this->l('Social title'),
					'name' => 'id_gender',
					'required' => false,
					'class' => 't',
					'values' => $list_genders
				),
				array(
					'type' => 'text',
					'label' => $this->l('First name'),
					'name' => 'firstname',
					'required' => true,
					'col' => '4',
					'hint' => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
				),
				array(
					'type' => 'text',
					'label' => $this->l('Last name'),
					'name' => 'lastname',
					'required' => true,
					'col' => '4',
					'hint' => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"°{}_$%:'
				),
				array(
					'type' => 'text',
					'prefix' => '<i class="icon-envelope-o"></i>',
					'label' => $this->l('Email address'),
					'name' => 'email',
					'col' => '4',
					'required' => true,
					'autocomplete' => false
				),
				array(
					'type' => 'password',
					'label' => $this->l('Password'),
					'name' => 'passwd',
					'required' => ($obj->id ? false : true),
					'col' => '4',
					'hint' => ($obj->id ? $this->l('Leave this field blank if there\'s no change.') :
						sprintf($this->l('Password should be at least %s characters long.'), Validate::PASSWORD_LENGTH))
				),
				array(
					'type' => 'birthday',
					'label' => $this->l('Birthday'),
					'name' => 'birthday',
					'options' => array(
						'days' => $days,
						'months' => $months,
						'years' => $years
					)
				),
				array(
					'type' => 'switch',
					'label' => $this->l('Enabled'),
					'name' => 'active',
					'required' => false,
					'class' => 't',
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					),
					'hint' => $this->l('Enable or disable customer login.')
				),
				array(
					'type' => 'switch',
					'label' => $this->l('Newsletter'),
					'name' => 'newsletter',
					'required' => false,
					'class' => 't',
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'newsletter_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'newsletter_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					),
					'disabled' =>  (bool)!Configuration::get('PS_CUSTOMER_NWSL'),
					'hint' => $this->l('This customer will receive your newsletter via email.')
				),
				array(
					'type' => 'switch',
					'label' => $this->l('Opt-in'),
					'name' => 'optin',
					'required' => false,
					'class' => 't',
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'optin_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'optin_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					),
					'disabled' =>  (bool)!Configuration::get('PS_CUSTOMER_OPTIN'),
					'hint' => $this->l('This customer will receive your ads via email.')
				),
			)
		);

		// if we add a customer via fancybox (ajax), it's a customer and he doesn't need to be added to the visitor and guest groups
		if (Tools::isSubmit('addcustomer') && Tools::isSubmit('submitFormAjax'))
		{
			$visitor_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
			$guest_group = Configuration::get('PS_GUEST_GROUP');
			foreach ($groups as $key => $g)
				if (in_array($g['id_group'], array($visitor_group, $guest_group)))
					unset($groups[$key]);
		}

		$this->fields_form['input'] = array_merge(
			$this->fields_form['input'],
			array(
				array(
					'type' => 'select',
					'label' => $this->l('Default customer group'),
					'name' => 'id_default_group',
					'options' => array(
						'query' => $groups,
						'id' => 'id_group',
						'name' => 'name'
					),
					'col' => '4',
					'hint' => array(
						$this->l('This group will be the user\'s default group.'),
						$this->l('Only the discount for the selected group will be applied to this customer.')
					)
				),
				array(
					'type' => 'group',
					'label' => $this->l('Group access'),
					'name' => 'groupBox',
					'values' => $groups,
					'required' => true,
					'col' => '6',
					'hint' => $this->l('Select all the groups that you would like to apply to this customer.')
				)

			)
		);

		// if customer is a guest customer, password hasn't to be there
		if ($obj->id && ($obj->is_guest && $obj->id_default_group == Configuration::get('PS_GUEST_GROUP')))
		{
			foreach ($this->fields_form['input'] as $k => $field)
				if ($field['type'] == 'password')
					array_splice($this->fields_form['input'], $k, 1);
		}

		if (Configuration::get('PS_B2B_ENABLE'))
		{
			$risks = Risk::getRisks();

			$list_risks = array();
			foreach ($risks as $key => $risk)
			{
				$list_risks[$key]['id_risk'] = (int)$risk->id;
				$list_risks[$key]['name'] = $risk->name;
			}

			$this->fields_form['input'][] = array(
				'type' => 'text',
				'label' => $this->l('Company'),
				'name' => 'company'
			);
			$this->fields_form['input'][] = array(
				'type' => 'text',
				'label' => $this->l('SIRET'),
				'name' => 'siret'
			);
			$this->fields_form['input'][] = array(
				'type' => 'text',
				'label' => $this->l('APE'),
				'name' => 'ape'
			);
			$this->fields_form['input'][] = array(
				'type' => 'text',
				'label' => $this->l('Website'),
				'name' => 'website'
			);
			$this->fields_form['input'][] = array(
				'type' => 'text',
				'label' => $this->l('Allowed outstanding amount'),
				'name' => 'outstanding_allow_amount',
				'hint' => $this->l('Valid characters:').' 0-9',
				'suffix' => $this->context->currency->sign
			);
			$this->fields_form['input'][] = array(
				'type' => 'text',
				'label' => $this->l('Maximum number of payment days'),
				'name' => 'max_payment_days',
				'hint' => $this->l('Valid characters:').' 0-9'
			);
			$this->fields_form['input'][] = array(
				'type' => 'select',
				'label' => $this->l('Risk rating'),
				'name' => 'id_risk',
				'required' => false,
				'class' => 't',
				'options' => array(
					'query' => $list_risks,
					'id' => 'id_risk',
					'name' => 'name'
				),
			);
		}

		$this->fields_form['submit'] = array(
			'title' => $this->l('Save'),
		);

		$birthday = explode('-', $this->getFieldValue($obj, 'birthday'));

		$this->fields_value = array(
			'years' => $this->getFieldValue($obj, 'birthday') ? $birthday[0] : 0,
			'months' => $this->getFieldValue($obj, 'birthday') ? $birthday[1] : 0,
			'days' => $this->getFieldValue($obj, 'birthday') ? $birthday[2] : 0,
		);

		// Added values of object Group
		if (!Validate::isUnsignedId($obj->id))
			$customer_groups = array();
		else
			$customer_groups = $obj->getGroups();
		$customer_groups_ids = array();
		if (is_array($customer_groups))
			foreach ($customer_groups as $customer_group)
				$customer_groups_ids[] = $customer_group;

		// if empty $carrier_groups_ids : object creation : we set the default groups
		if (empty($customer_groups_ids))
		{
			$preselected = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'), Configuration::get('PS_CUSTOMER_GROUP'));
			$customer_groups_ids = array_merge($customer_groups_ids, $preselected);
		}

		foreach ($groups as $group)
			$this->fields_value['groupBox_'.$group['id_group']] =
				Tools::getValue('groupBox_'.$group['id_group'], in_array($group['id_group'], $customer_groups_ids));

			$this->context->controller->addJqueryUI('ui.autocomplete.js' );
						$added="ciao

						<div id='customers'>
						</div>
		<script type='text/javascript'>

			$(document).ready(function() {

			$('.parrain').typeWatch({
			captureLength: 1,
			highlight: true,
			wait: 100,
			callback: function(){ searchCustomers(); }
			});

		function searchCustomers()
		{
		$.ajax({
			type:'POST',
			url : 'index.php?controller=AdminCustomers&token=27f83bf88a8468b80977e24f10d473e7',
			async: true,
			dataType: 'json',
			data : {
				ajax: '1',
				tab: 'AdminCustomers',
				action: 'searchCustomers',
				customer_search: $('#customer').val()},
			success : function(res)
			{
				if(res.found)
				{
					var html = '';
					$.each(res.customers, function() {
						html += '<div class=\'customerCard col-lg-4\'>';
						html += '<div class=\'panel\'>';
						html += '<div class=\'panel-heading\'>'+this.firstname+' '+this.lastname;
						html += '<span class=\'pull-right\'>#'+this.id_customer+'</span></div>';
						html += '<span>'+this.email+'</span><br/>';
						html += '<span class=\'text-muted\'>'+((this.birthday != '0000-00-00') ? this.birthday : '')+'</span><br/>';
						html += '<div class=\'panel-footer\'>';
						html += '<a href=\'http://www.l-et-sens.com/newshop/adminlsens/index.php?controller=AdminCustomers&token=27f83bf88a8468b80977e24f10d473e7&id_customer='+this.id_customer+'&viewcustomer&liteDisplaying=1\' class=\'btn btn-default fancybox\'><i class=\'icon-search\'></i> {l s=\'Details\'}</a>';
						html += '<button type=\'button\' data-customer=\''+this.id_customer+'\' class=\'setup-customer btn btn-default pull-right\'><i class=\'icon-arrow-right\'></i> Choose</button>';
						html += '</div>';
						html += '</div>';
						html += '</div>';
					});
				}
				else
					html = '<div class=\'alert alert-warning\'><i class=\'icon-warning-sign\'></i>&nbsp;No customers found</div>';
				$('#customers').html(html);
				resetBind();
			}
		});
	}






			});
			</script>

		";

		return parent::renderForm().$added;
	}

	public function beforeAdd($customer)
	{
		$customer->id_shop = $this->context->shop->id;
	}

	public function renderKpis()
	{
		$time = time();
		$kpis = array();

		/* The data generation is located in AdminStatsControllerCore */

		$helper = new HelperKpi();
		$helper->id = 'box-gender';
		$helper->icon = 'icon-male';
		$helper->color = 'color1';
		$helper->title = $this->l('Customers', null, null, false);
		$helper->subtitle = $this->l('All Time', null, null, false);
		if (ConfigurationKPI::get('CUSTOMER_MAIN_GENDER', $this->context->language->id) !== false)
			$helper->value = ConfigurationKPI::get('CUSTOMER_MAIN_GENDER', $this->context->language->id);
		$helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=customer_main_gender';
		$helper->refresh = (bool)(ConfigurationKPI::get('CUSTOMER_MAIN_GENDER_EXPIRE', $this->context->language->id) < $time);
		$kpis[] = $helper->generate();

		$helper = new HelperKpi();
		$helper->id = 'box-age';
		$helper->icon = 'icon-calendar';
		$helper->color = 'color2';
		$helper->title = $this->l('Average Age', 'AdminTab', null, false);
		$helper->subtitle = $this->l('All Time', null, null, false);
		if (ConfigurationKPI::get('AVG_CUSTOMER_AGE', $this->context->language->id) !== false)
			$helper->value = ConfigurationKPI::get('AVG_CUSTOMER_AGE', $this->context->language->id);
		$helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=avg_customer_age';
		$helper->refresh = (bool)(ConfigurationKPI::get('AVG_CUSTOMER_AGE_EXPIRE', $this->context->language->id) < $time);
		$kpis[] = $helper->generate();

		$helper = new HelperKpi();
		$helper->id = 'box-orders';
		$helper->icon = 'icon-retweet';
		$helper->color = 'color3';
		$helper->title = $this->l('Orders per Customer', null, null, false);
		$helper->subtitle = $this->l('All Time', null, null, false);
		if (ConfigurationKPI::get('ORDERS_PER_CUSTOMER') !== false)
			$helper->value = ConfigurationKPI::get('ORDERS_PER_CUSTOMER');
		$helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=orders_per_customer';
		$helper->refresh = (bool)(ConfigurationKPI::get('ORDERS_PER_CUSTOMER_EXPIRE') < $time);
		$kpis[] = $helper->generate();

		$helper = new HelperKpi();
		$helper->id = 'box-newsletter';
		$helper->icon = 'icon-envelope';
		$helper->color = 'color4';
		$helper->title = $this->l('Newsletter Registrations', null, null, false);
		$helper->subtitle = $this->l('All Time', null, null, false);
		if (ConfigurationKPI::get('NEWSLETTER_REGISTRATIONS') !== false)
			$helper->value = ConfigurationKPI::get('NEWSLETTER_REGISTRATIONS');
		$helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=newsletter_registrations';
		$helper->refresh = (bool)(ConfigurationKPI::get('NEWSLETTER_REGISTRATIONS_EXPIRE') < $time);
		$kpis[] = $helper->generate();

		$helper = new HelperKpiRow();
		$helper->kpis = $kpis;
		return $helper->generate();
	}

	public function renderView()
	{
		if (!($customer = $this->loadObject()))
			return;

		$this->context->customer = $customer;
		$gender = new Gender($customer->id_gender, $this->context->language->id);
		$gender_image = $gender->getImage();

		$customer_stats = $customer->getStats();
		$sql = 'SELECT SUM(total_paid_real) FROM '._DB_PREFIX_.'orders WHERE id_customer = %d AND valid = 1';
		if ($total_customer = Db::getInstance()->getValue(sprintf($sql, $customer->id)))
		{
			$sql = 'SELECT SQL_CALC_FOUND_ROWS COUNT(*) FROM '._DB_PREFIX_.'orders WHERE valid = 1 AND id_customer != '.(int)$customer->id.' GROUP BY id_customer HAVING SUM(total_paid_real) > %d';
			Db::getInstance()->getValue(sprintf($sql, (int)$total_customer));
			$count_better_customers = (int)Db::getInstance()->getValue('SELECT FOUND_ROWS()') + 1;
		}
		else
			$count_better_customers = '-';

		$orders = Order::getCustomerOrders($customer->id, true);
		$total_orders = count($orders);
		for ($i = 0; $i < $total_orders; $i++)
		{
			$orders[$i]['total_paid_real_not_formated'] = $orders[$i]['total_paid_real'];
			$orders[$i]['total_paid_real'] = Tools::displayPrice($orders[$i]['total_paid_real'], new Currency((int)$orders[$i]['id_currency']));
		}

		$messages = CustomerThread::getCustomerMessages((int)$customer->id);
		$total_messages = count($messages);
		for ($i = 0; $i < $total_messages; $i++)
		{
			$messages[$i]['message'] = substr(strip_tags(html_entity_decode($messages[$i]['message'], ENT_NOQUOTES, 'UTF-8')), 0, 75);
			$messages[$i]['date_add'] = Tools::displayDate($messages[$i]['date_add'], null, true);
		}

		$groups = $customer->getGroups();
		$total_groups = count($groups);
		for ($i = 0; $i < $total_groups; $i++)
		{
			$group = new Group($groups[$i]);
			$groups[$i] = array();
			$groups[$i]['id_group'] = $group->id;
			$groups[$i]['name'] = $group->name[$this->default_form_language];
		}

		$total_ok = 0;
		$orders_ok = array();
		$orders_ko = array();
		foreach ($orders as $order)
		{
			if (!isset($order['order_state']))
				$order['order_state'] = $this->l('There is no status defined for this order.');

			if ($order['valid'])
			{
				$orders_ok[] = $order;
				$total_ok += $order['total_paid_real_not_formated'];
			}
			else
				$orders_ko[] = $order;
		}

		$products = $customer->getBoughtProducts();

		$carts = Cart::getCustomerCarts($customer->id);
		$total_carts = count($carts);
		for ($i = 0; $i < $total_carts; $i++)
		{
			$cart = new Cart((int)$carts[$i]['id_cart']);
			$this->context->cart = $cart;
			$summary = $cart->getSummaryDetails();
			$currency = new Currency((int)$carts[$i]['id_currency']);
			$carrier = new Carrier((int)$carts[$i]['id_carrier']);
			$carts[$i]['id_cart'] = sprintf('%06d', $carts[$i]['id_cart']);
			$carts[$i]['date_add'] = Tools::displayDate($carts[$i]['date_add'], null, true);
			$carts[$i]['total_price'] = Tools::displayPrice($summary['total_price'], $currency);
			$carts[$i]['name'] = $carrier->name;
		}

		$sql = 'SELECT DISTINCT cp.id_product, c.id_cart, c.id_shop, cp.id_shop AS cp_id_shop
				FROM '._DB_PREFIX_.'cart_product cp
				JOIN '._DB_PREFIX_.'cart c ON (c.id_cart = cp.id_cart)
				JOIN '._DB_PREFIX_.'product p ON (cp.id_product = p.id_product)
				WHERE c.id_customer = '.(int)$customer->id.'
					AND cp.id_product NOT IN (
							SELECT product_id
							FROM '._DB_PREFIX_.'orders o
							JOIN '._DB_PREFIX_.'order_detail od ON (o.id_order = od.id_order)
							WHERE o.valid = 1 AND o.id_customer = '.(int)$customer->id.'
						)';
		$interested = Db::getInstance()->executeS($sql);
		$total_interested = count($interested);
		for ($i = 0; $i < $total_interested; $i++)
		{
			$product = new Product($interested[$i]['id_product'], false, $this->default_form_language, $interested[$i]['id_shop']);
			if (!Validate::isLoadedObject($product))
				continue;
			$interested[$i]['url'] = $this->context->link->getProductLink(
				$product->id,
				$product->link_rewrite,
				Category::getLinkRewrite($product->id_category_default, $this->default_form_language),
				null,
				null,
				$interested[$i]['cp_id_shop']
			);
			$interested[$i]['id'] = (int)$product->id;
			$interested[$i]['name'] = Tools::htmlentitiesUTF8($product->name);
		}

		$emails = $customer->getLastEmails();

		$connections = $customer->getLastConnections();
		if (!is_array($connections))
			$connections = array();
		$total_connections = count($connections);
		for ($i = 0; $i < $total_connections; $i++)
			$connections[$i]['http_referer'] = $connections[$i]['http_referer'] ? preg_replace('/^www./', '', parse_url($connections[$i]['http_referer'], PHP_URL_HOST)) : $this->l('Direct link');

		$referrers = Referrer::getReferrers($customer->id);
		$total_referrers = count($referrers);
		for ($i = 0; $i < $total_referrers; $i++)
			$referrers[$i]['date_add'] = Tools::displayDate($referrers[$i]['date_add'],null , true);

		$customerLanguage = new Language($customer->id_lang);
		$shop = new Shop($customer->id_shop);
		$this->tpl_view_vars = array(
			'customer' => $customer,
			'gender' => $gender,
			'gender_image' => $gender_image,
			// General information of the customer
			'registration_date' => Tools::displayDate($customer->date_add,null , true),
			'customer_stats' => $customer_stats,
			'last_visit' => Tools::displayDate($customer_stats['last_visit'],null , true),
			'count_better_customers' => $count_better_customers,
			'shop_is_feature_active' => Shop::isFeatureActive(),
			'name_shop' => $shop->name,
			'customer_birthday' => Tools::displayDate($customer->birthday),
			'last_update' => Tools::displayDate($customer->date_upd,null , true),
			'customer_exists' => Customer::customerExists($customer->email),
			'id_lang' => $customer->id_lang,
			'customerLanguage' => $customerLanguage,
			// Add a Private note
			'customer_note' => Tools::htmlentitiesUTF8($customer->note),
			// Messages
			'messages' => $messages,
			// Groups
			'groups' => $groups,
			// Orders
			'orders' => $orders,
			'orders_ok' => $orders_ok,
			'orders_ko' => $orders_ko,
			'total_ok' => Tools::displayPrice($total_ok, $this->context->currency->id),
			// Products
			'products' => $products,
			// Addresses
            'addresses' => $this->makePhoneCallFiche($customer->getAddresses($this->default_form_language)), // keyyo
			// 'addresses' => $customer->getAddresses($this->default_form_language),
			// Discounts
			'discounts' => CartRule::getCustomerCartRules($this->default_form_language, $customer->id, false, false),
			// Carts
			'carts' => $carts,
			// Interested
			'interested' => $interested,
			// Emails
			'emails' => $emails,
			// Connections
			'connections' => $connections,
			// Referrers
			'referrers' => $referrers,
			'show_toolbar' => true
		);



		return parent::renderView();
	}

	public function processDelete()
	{
		$this->_setDeletedMode();
		parent::processDelete();
	}

	protected function _setDeletedMode()
	{
		if ($this->delete_mode == 'real')
			$this->deleted = false;
		elseif ($this->delete_mode == 'deleted')
			$this->deleted = true;
		else
		{
			$this->errors[] = Tools::displayError('Unknown delete mode:').' '.$this->deleted;
			return;
		}
	}

	protected function processBulkDelete()
	{
		$this->_setDeletedMode();
		parent::processBulkDelete();
	}

	public function processAdd()
	{
		if (Tools::getValue('submitFormAjax'))
			$this->redirect_after = false;
		// Check that the new email is not already in use
		$customer_email = strval(Tools::getValue('email'));
		$customer = new Customer();
		if (Validate::isEmail($customer_email))
			$customer->getByEmail($customer_email);
		if ($customer->id)
		{
			$this->errors[] = Tools::displayError('An account already exists for this email address:').' '.$customer_email;
			$this->display = 'edit';
			return $customer;
		}
		elseif (trim(Tools::getValue('passwd')) == '')
		{
			$this->validateRules();
			$this->errors[] = Tools::displayError('Password can not be empty.');
			$this->display = 'edit';
		}
		elseif ($customer = parent::processAdd())
		{
			// added
			$sponsor_id=Tools::getValue('id_sponsor');
			if ($sponsor_id != "" )
			{


				$sponsor_infos=new Customer((int)$sponsor_id);
				$sponsored_infos=$customer;
				include_once(_PS_MODULE_DIR_.'tbwebreferralprogram/TbwebReferralProgramModule.php');

				//$sponsored =
				if (!$id_referralprogram = TbwebReferralProgramModule::isEmailExists($customer->email, true, false))
					{
						$referralprogram = new TbwebReferralProgramModule();
						$referralprogram->id_sponsor = (int)$sponsor_infos->id;
						$referralprogram->firstname = $sponsored_infos->firstname;
						$referralprogram->lastname = $sponsored_infos->lastname;
						$referralprogram->email = $sponsored_infos->email;

						if (!$referralprogram->validateFields(false))
							return false;
						else
							$referralprogram->save();
					}
				else
				{
					$referralprogram = new TbwebReferralProgramModule((int)$id_referralprogram);
				}



								if ($referralprogram->save())
								{


									$referralprogram->id_customer = (int)$sponsored_infos->id;
									$referralprogram->save();

									if (Configuration::get('TBWEBREFERRAL_DISCOUNT_SP_ACTIVE'))
									{
										//echo Configuration::get('PS_CURRENCY_DEFAULT');

										if ($referralprogram->registerDiscountForSponsoredAdmin((int)Configuration::get('PS_CURRENCY_DEFAULT'),(int)$sponsor_infos->id))
										{
											$cartRule = new CartRule((int)$referralprogram->id_cart_rule);
											$this->context->smarty->assign('redution_libele', $cartRule->code);

										}



										if (Configuration::get('PS_CIPHER_ALGORITHM'))
											$cipherTool = new Rijndael(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
										else
											$cipherTool = new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);

									/*$vars = array(
											'{email}' => $sponsor_infos->email,
											'{lastname}' => $sponsor_infos->lastname,
											'{firstname}' => $sponsor_infos->firstname,
											'{email_friend}' => $sponsored_infos->email,
											'{lastname_friend}' => $sponsored_infos->lastname,
											'{firstname_friend}' => $sponsored_infos->firstname,
											'{link}' => Context::getContext()->link->getPageLink('authentication', true, Context::getContext()->language->id, 'create_account=1&sponsor='.urlencode($cipherTool->encrypt($referralprogram->id.'|'.$referralprogram->email.'|')).'&back=my-account', false),
											'{discount}' => $discount);*/


											//Mail::Send((int)$this->context->language->id, 'referralprogram-invitation', Mail::l('Referral Program', (int)$this->context->language->id), $vars, $friendEmail, $friendFirstName.' '.$friendLastName, strval(Configuration::get('PS_SHOP_EMAIL')), strval(Configuration::get('PS_SHOP_NAME')), NULL, NULL, dirname(__FILE__).'/../../mails/');
									}


							 }
			}

			$this->context->smarty->assign('new_customer', $customer);
			return $customer;
		}
		return false;
	}

	public function processUpdate()
	{
		if (Validate::isLoadedObject($this->object))
		{
			$customer_email = strval(Tools::getValue('email'));

			// check if e-mail already used
			if ($customer_email != $this->object->email)
			{
				$customer = new Customer();
				if (Validate::isEmail($customer_email))
					$customer->getByEmail($customer_email);
				if (($customer->id) && ($customer->id != (int)$this->object->id))
					$this->errors[] = Tools::displayError('An account already exists for this email address:').' '.$customer_email;
			}

			return parent::processUpdate();
		}
		else
			$this->errors[] = Tools::displayError('An error occurred while loading the object.').'
				<b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
	}

	public function processSave()
	{
		// Check that default group is selected
		if (!is_array(Tools::getValue('groupBox')) || !in_array(Tools::getValue('id_default_group'), Tools::getValue('groupBox')))
			$this->errors[] = Tools::displayError('A default customer group must be selected in group box.');

		// Check the requires fields which are settings in the BO
		$customer = new Customer();
		$this->errors = array_merge($this->errors, $customer->validateFieldsRequiredDatabase());

		return parent::processSave();
	}

	protected function afterDelete($object, $old_id)
	{
		$customer = new Customer($old_id);
		$addresses = $customer->getAddresses($this->default_form_language);
		foreach ($addresses as $k => $v)
		{
			$address = new Address($v['id_address']);
			$address->id_customer = $object->id;
			$address->save();
		}
		return true;
	}
	/**
	 * Transform a guest account into a registered customer account
	 */
	public function processGuestToCustomer()
	{
		$customer = new Customer((int)Tools::getValue('id_customer'));
		if (!Validate::isLoadedObject($customer))
			$this->errors[] = Tools::displayError('This customer does not exist.');
		if (Customer::customerExists($customer->email))
			$this->errors[] = Tools::displayError('This customer already exists as a non-guest.');
		elseif ($customer->transformToCustomer(Tools::getValue('id_lang', $this->context->language->id)))
			Tools::redirectAdmin(self::$currentIndex.'&'.$this->identifier.'='.$customer->id.'&conf=3&token='.$this->token);
		else
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
	}

	/**
	 * Toggle the newsletter flag
	 */
	public function processChangeNewsletterVal()
	{
		$customer = new Customer($this->id_object);
		if (!Validate::isLoadedObject($customer))
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		$customer->newsletter = $customer->newsletter ? 0 : 1;
		if (!$customer->update())
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
	}

	/**
	 * Toggle newsletter optin flag
	 */
	public function processChangeOptinVal()
	{
		$customer = new Customer($this->id_object);
		if (!Validate::isLoadedObject($customer))
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		$customer->optin = $customer->optin ? 0 : 1;
		if (!$customer->update())
			$this->errors[] = Tools::displayError('An error occurred while updating customer information.');
		Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token);
	}

	public function printNewsIcon($value, $customer)
	{
		return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab=AdminCustomers&id_customer='
			.(int)$customer['id_customer'].'&changeNewsletterVal&token='.Tools::getAdminTokenLite('AdminCustomers')).'">
				'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').
			'</a>';
	}

	public function printOptinIcon($value, $customer)
	{
		return '<a class="list-action-enable '.($value ? 'action-enabled' : 'action-disabled').'" href="index.php?'.htmlspecialchars('tab=AdminCustomers&id_customer='
			.(int)$customer['id_customer'].'&changeOptinVal&token='.Tools::getAdminTokenLite('AdminCustomers')).'">
				'.($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>').
			'</a>';
	}

	/**
	 * @param string $token
	 * @param integer $id
	 * @param string $name
	 * @return mixed
	 */
	public function displayDeleteLink($token = null, $id, $name = null)
	{
		$tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');

		$customer = new Customer($id);
		$name = $customer->lastname.' '.$customer->firstname;
		$name = '\n\n'.$this->l('Name:', 'helper').' '.$name;

		$tpl->assign(array(
			'href' => self::$currentIndex.'&'.$this->identifier.'='.$id.'&delete'.$this->table.'&token='.($token != null ? $token : $this->token),
			'confirm' => $this->l('Delete the selected item?').$name,
			'action' => $this->l('Delete'),
			'id' => $id,
		));

		return $tpl->fetch();
	}

	/**
	 * add to $this->content the result of Customer::SearchByName
	 * (encoded in json)
	 *
	 * @return void
	 */
	public function ajaxProcessSearchCustomers()
	{
		$searches = explode(' ', Tools::getValue('customer_search'));
		$customers = array();
		$searches = array_unique($searches);
		foreach ($searches as $search)
			if (!empty($search) && $results = Customer::searchByName($search))
				foreach ($results as $result)
					$customers[$result['id_customer']] = $result;

		if (count($customers))
			$to_return = array(
				'customers' => $customers,
				'found' => true
			);
		else
			$to_return = array('found' => false);

		$this->content = Tools::jsonEncode($to_return);
	}

	/**
	 * Uodate the customer note
	 *
	 * @return void
	 */
	public function ajaxProcessUpdateCustomerNote()
	{
		if ($this->tabAccess['edit'] === '1')
		{
			$note = Tools::htmlentitiesDecodeUTF8(Tools::getValue('note'));
			$customer = new Customer((int)Tools::getValue('id_customer'));
			if (!Validate::isLoadedObject($customer))
				die ('error:update');
			if (!empty($note) && !Validate::isCleanHtml($note))
				die ('error:validation');
			$customer->note = $note;
			if (!$customer->update())
				die ('error:update');
			die('ok');
		}
	}

    /**
     * Création de l'url pour l'appel ajax vers le client depuis un poste KEYYO
     * 02/08/2016 Dominique
     * @param $number
     * @param $params
     * @return string
     */
    public function makePhoneCall($number, $params)
    {
        $keyyo_link ='';
        $phoneNumbers = explode(':', $number);
        foreach ($phoneNumbers as $phoneNumber) {
            $NumberK = $this->sanitizePhoneNumber($phoneNumber);
            $ln = strlen($NumberK);

            $display_message = ($ln != 10 && $ln > 0) ? '<i class="icon-warning text-danger"></i>' : '';

            $keyyo_link .= $display_message . ' <a href="' . Context::getContext()->link->getAdminLink('AdminCustomers');
            $keyyo_link .= '&ajax=1&action=KeyyoCall';
            $keyyo_link .= '&CALLEE=' . $NumberK;
            $keyyo_link .= '&CALLE_NAME=' . $params['lastname'] . '_' . $params['firstname'];
            $keyyo_link .= '" class="keyyo_link">' . $NumberK . '</a>';
        }
        return $keyyo_link;
    }

    private function sanitizePhoneNumber($number)
    {
        $pattern = str_split(Configuration::get('KEYYO_NUMBER_FILTER'));
        $number = str_replace($pattern, '', $number);
        if (substr($number, 0, 1) != '0') {
            $number = '0' . $number;
        }

        return $number;
    }

    public function ajaxProcessKeyyoCall()
    {

        $keyyo_url = Configuration::get('KEYYO_URL');
        $account = $this->context->employee->getKeyyoCaller();
        $callee = Validate::isString(Tools::getValue('CALLEE'))?Tools::getValue('CALLEE'):'';
        $calle_name = Validate::isString(Tools::getValue('CALLE_NAME'))?Tools::getValue('CALLE_NAME'):'';

        if (!$account) {
            $return = Tools::jsonEncode(array('msg' => 'Veuillez configurer votre numéro de compte KEYYO.'));
            die($return);
        }

        if (!$callee || !$calle_name) {
            $return = Tools::jsonEncode(array('msg' => 'Il manque une information pour composer le numéro.'));
            die($return);
        } else {
            $keyyo_link = $keyyo_url . '?ACCOUNT=' . $account;
            $keyyo_link .= '&CALLEE=' . $callee;
            $keyyo_link .= '&CALLE_NAME=' . $calle_name;


            $fp = fopen($keyyo_link, 'r');
            $buffer = fgets($fp, 4096);
            fclose($fp);

            if ($buffer == 'OK') {
                $return = Tools::jsonEncode(array('msg' => 'Appel du ' . $callee . ' en cours.'));
                die($return);
            } else {
                $return = Tools::jsonEncode(array('msg' => 'Problème lors de l\'appel.'));
                die($return);
            }
        }
    }


    public function makePhoneCallFiche($customer)
    {
        if (!empty($customer)) {
            $customer[0]['phone'] = ($customer[0]['phone']) ? $this->makeUrlKeyyoFiche($customer[0]['phone'], $customer) : '';
            $customer[0]['phone_mobile'] = ($customer[0]['phone_mobile']) ? $this->makeUrlKeyyoFiche($customer[0]['phone_mobile'], $customer) : '';
        }

        return $customer;
    }

    public function makeUrlKeyyoFiche($phoneNumber, $customer)
    {
            $keyyo_link = '';
            $NumberK = $this->sanitizePhoneNumber($phoneNumber);
            $ln = strlen($NumberK);

            $display_message = ($ln != 10 && $ln > 0) ? '<i class="icon-warning text-danger"></i>' : '';

            $keyyo_link .= $display_message . ' <a href="' . Context::getContext()->link->getAdminLink('AdminCustomers');
            $keyyo_link .= '&ajax=1&action=KeyyoCall';
            $keyyo_link .= '&CALLEE=' . $NumberK;
            $keyyo_link .= '&CALLE_NAME=' . $customer[0]['lastname'] . '_' . $customer[0]['firstname'];
            $keyyo_link .= '" class="keyyo_link">' . $NumberK . '</a>';

    return $keyyo_link;
    }

}
