<?php

/**
 * AdminKeyyo File Doc Comment
 * AdminKeyyo Class Doc Comment
 *
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 * @author    Dominique <dominique@chez-dominique.fr
 * @copyright 2007-2016 PrestaShop SA / 2011-2016 Dominique
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registred Trademark & Property of PrestaShop SA
 */

require_once(dirname(__FILE__) . '/../../classes/NotificationKeyyoClass.php');

class AdminKeyyoController extends ModuleAdminController
{

    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }
        $this->table = 'notification_keyyo';
        $this->module = 'keyyo';
        $this->className = 'NotificationKeyyoClass';
        $this->identifier = 'id_notification_keyyo';
        $this->lang = false;
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->addCSS(_PS_MODULE_DIR_ . 'keyyo/views/css/adminkeyyo.css');
        $this->list_no_link = true;
        $this->_orderBy = 'id_notification_keyyo';
        $this->_orderWay = 'DESC';

        $this->fields_list = array(
            'id_notification_keyyo' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'),
            'account' => array(
                'title' => $this->l('N° KEYYO'),
                'class' => 'col-md-1',
            ),
            'callee' => array(
                'title' => $this->l('Pour le'),
                'class' => 'col-md-1',
            ),
            'redirectingnumber' => array(
                'title' => $this->l('Renvoi de'),
                'class' => 'col-md-1',
                'callback' => 'whoIsNumber',
            ),
            'tsms' => array(
                'title' => $this->l('Heure'),
                'class' => 'col-md-2',
                'callback' => 'formatTime'
            ),
            'type' => array(
                'title' => $this->l('Type'),
                'class' => 'col-md-1',
                'align' => 'center',
                'callback' => 'setType'
            ),
            'status' => array(
                'title' => $this->l('ok'),
                'class' => 'fixed-width-xs',
            ),
            'caller' => array(
                'title' => $this->l('Appel de'),
                'class' => 'col-md-3 numberCaller',
                'callback' => 'linkRappel'
            ),
        );

        $this->bulk_action = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            ),
        );
        parent::__construct();

        $this->addJquery();
        $this->addJS(_PS_MODULE_DIR_ . 'keyyo/views/js/jquery.cookie.js');
    }

    public function renderList()
    {
        if (isset($this->_filter) && trim($this->_filter) == '')
            $this->_filter = $this->original_filter;

        $this->addRowAction('view');
        $this->addRowAction('add');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function initPageHeaderToolbar()
    {
        if (!$this->display) {
            $this->page_header_toolbar_btn['new'] = array(
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Add New')
            );
        } else if ($this->display == 'view') {
            $this->page_header_toolbar_btn['back'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back to the list')
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderView()
    {
        $this->tpl_view_vars['notification_keyyo'] = $this->loadObject();
        return parent::renderView();
    }

    public function formatTime($t)
    {
        return date('d-m-Y à H:i:s', substr($t, 0, 10));
    }

    /**
     * Création de l'url pour l'appel ajax vers le client
     * @param $number
     * @param $params
     * @return string
     */
    public function makePhoneCall($number, $params)
    {
        $phoneNumber = $this->sanitizePhoneNumber($number);
        $ln = strlen($phoneNumber);
        $display_message = ($ln != 10 && $ln > 0) ? '<i class="icon-warning text-danger"></i>' : '';

        $params['lastname'] = str_replace(' ', '_', trim($params['lastname']));
        $params['firstname'] = str_replace(' ', '_', trim($params['firstname']));


        $keyyo_link = $display_message . ' <a href="' . Context::getContext()->link->getAdminLink('AdminKeyyo');
        $keyyo_link .= '&ajax=1&action=KeyyoCall';
        $keyyo_link .= '&CALLEE=' . $phoneNumber;
        $keyyo_link .= '&CALLE_NAME=' . $params['lastname'] . '_' . $params['firstname'];
        $keyyo_link .= '" class="keyyo_link">' . $phoneNumber . '</a>';

        return $keyyo_link;
    }

    /**
     * Renvoie le lien vers la fiche client
     * @param $token
     * @param $id
     * @return string
     */
    public function displayAfficherLink($token, $id)
    {
        $tokenLite = Tools::getAdminTokenLite('AdminCustomers');
        $link =
            self::$currentIndex . '&controller=AdminCustomers&id_customer=' . $id . '&viewcustomer&token=' . $tokenLite;
        return '<a href="' . $link . '" class="icon-search-plus"> Afficher</a>';
    }

    public function ajaxProcessKeyyoCall()
    {
        $log = array();
        $keyyo_url = Configuration::get('KEYYO_URL');
        $account = $this->context->employee->getKeyyoCaller();
        $callee = Validate::isString(Tools::getValue('CALLEE')) ? Tools::getValue('CALLEE') : '';
        $calle_name = Validate::isString(Tools::getValue('CALLE_NAME')) ? Tools::getValue('CALLE_NAME') : '';

        $log = array(
            'keyyo_url' => $keyyo_url,
            'account' => $account,
            'callee' => $callee,
            'calle_name' => $calle_name,
            'erreur' => '',
            'keyyo_link' => '',
            'retour_keyyo' => '',
            'heure' => date('H:i:s d-m-Y')
        );

        if (!$account) {
            $return = Tools::jsonEncode(array('msg' => 'Veuillez configurer votre numéro de compte KEYYO.'));
            $log['erreur'] = $return;
            $this->logKeyyo($log);
            die($return);
        }

        if (!$callee || !$calle_name) {
            $return = Tools::jsonEncode(array('msg' => 'Il manque une information pour composer le numéro.'));
            $log['erreur'] = $return;
            $this->logKeyyo($log);
            die($return);
        } else {
            $keyyo_link = $keyyo_url . '?ACCOUNT=' . $account;
            $keyyo_link .= '&CALLEE=' . $callee;
            $keyyo_link .= '&CALLE_NAME=' . $calle_name;

            $fp = fopen($keyyo_link, 'r');
            $buffer = fgets($fp, 4096);
            fclose($fp);

            $log['keyyo_link'] = $keyyo_link;
            $log['retour_keyyo'] = $buffer;

            if ($buffer == 'OK') {
                $return = Tools::jsonEncode(array('msg' => 'Appel du ' . $callee . ' en cours.'));
                $log['erreur'] = $return;
                $this->logKeyyo($log);
                die($return);
            } else {
                $return = Tools::jsonEncode(array('msg' => 'Problème lors de l\'appel.'));
                $log['erreur'] = $return;
                $this->logKeyyo($log);
                die($return);
            }
        }
    }

    public function logKeyyo($log)
    {
        $f = Tools::jsonEncode($log);
        $file = fopen(_PS_MODULE_DIR_ . '/logKeyyo.txt', 'a+');
        fwrite($file, $f . PHP_EOL);
        fclose($file);
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


    public function ajaxProcessAffichageAppels()
    {
        $notif = array(
            'show' => 'true',
            'heureClient' => '',
            'heureServeur' => '',
            'callee' => '',
            'caller' => '',
            'redirectingNumber' => '',
            'linkCustomer' => '',
            'message' => '',
            'callerName' => '',
            'histoMessage' => array(),
            'dateMessage' => '',
            'messageHistorique' => '',
            'id_customer' => '',
            'id_employee' => '',
            'linkPostComment' => '',
            'historique_contact' => '',
            'dref' => ''
        );

        // Est-ce que l'employé peut afficher les notifications ?
        if (!$this->context->employee->keyyo_notification_enabled) {
            $notif['message'] = $this->l('Vous ne pouvez pas afficher les notifications');
            $notif['show'] = 'false';
            die(Tools::jsonEncode($notif));
        }

        $lastCall = $this->getHeureLastCall();
        $notif['dref'] = $lastCall['dref'];
        $notif['caller'] = $lastCall['caller'];
        $notif['callee'] = $lastCall['callee'];
        $notif['redirectingNumber'] = $this->whoIsNumber($lastCall['redirectingnumber']);
        $listeNumerosAcceptes = explode(',', $this->context->employee->keyyo_notification_numbers);
        $heureLastNotificationCient = Tools::getValue('heureLN');
        $heureLastNotificationServeur = $lastCall['tsms'];


        // Est-ce que le numéro appelé fait partir des numéros surveiller par l'employé ?
        if (in_array($notif['callee'], $listeNumerosAcceptes)) {

            // Est-ce qu'il s'agit d'une nouvelle notification ?
            if ($this->newDisplay($heureLastNotificationCient, $heureLastNotificationServeur)) {
                // on synchronise le serveur et le client
                $notif['heureClient'] = $heureLastNotificationServeur;
                $notif['heureServeur'] = $heureLastNotificationServeur;
                $notif['show'] = 'true';

                $query = new DbQuery();
                $query->select('a.*, c.*')
                    ->from('address', 'a')
                    ->leftJoin('customer', 'c', 'a.id_customer = c.id_customer')
                    ->where('a.phone LIKE "%' . pSQL(substr($lastCall['caller'], 2)) . '" OR a.phone_mobile LIKE "%' . pSQL(substr($lastCall['caller'], 2)) . '"')
                    ->orderBy('c.date_upd DESC');

                $results = Db::getInstance()->getRow($query);
                // Si le numéro caller est trouvé
                if ($results) {
                    // Création du lien vers la fiche client
                    $tokenLite = Tools::getAdminTokenLite('AdminCustomers');
                    $link = self::$currentIndex . '&controller=AdminCustomers&id_customer=' . $results['id_customer']
                        . '&viewcustomer&token=' . $tokenLite;
                    $notif['linkCustomer'] = $link;

                    $notif['callerName'] = strtoupper($results['lastname']) . ' ' . ucfirst($results['firstname']);
                    $notif['id_customer'] = $results['id_customer'];
                    $notif['id_employe'] = $results['id_employee'];

                    $query = new DbQuery();
                    $query->select('cc.*')
                        ->from('customer_comments', 'cc')
                        ->where('id_customer = "' . pSQL($results['id_customer']) . '"')
                        ->orderBy('cc.date_posted DESC')
                        ->limit(5);
                    $resultsComments = Db::getInstance()->executeS($query);

                    foreach ($resultsComments as $result) {
                        $employe = new Employee($result['id_employee']);

                        $notif['histoMessage'][] = '<tr><td><p>' . $employe->firstname[0] . '. ' . $employe->lastname
                            . '</p><p>' . $result['date_posted'] . '</p></td><td>' . $result['comment'] . '</td></tr>';
                    }

                    $notif['messageHistorique'] = 'Merci de rappeler ' . $notif['callerName'] . ' à '
                        . date('H:i:s \l\e d-m-Y', substr($notif['heureServeur'], 0, 10))
                        . ' Numéro : ' . wordwrap('+' . $notif['caller'], 2, " ", 1);
                    $notif['message'] = 'Numéro trouvé.';
                } else {
                    $notif['messageHistorique'] = 'Merci de rappeler le ' . wordwrap('+' . $notif['caller'], 2, " ", 1) . ' à '
                        . date('H:i:s \l\e d-m-Y', substr($notif['heureServeur'], 0, 10));
                    $notif['message'] = 'Numéro non trouvé.';
                }


                $tokenLiteComment = Tools::getAdminTokenLite('AdminKeyyo');
                $notif['linkPostComment'] = self::$currentIndex . '&controller=AdminKeyyo&ajax=1&action=KeyyoComment&token='
                    . $tokenLiteComment;

                $notif['dateMessage'] = date('Y-m-d à H:i:s', substr($notif['heureServeur'], 0, 10));
                die(Tools::jsonEncode($notif));
            }
        }

        $notif = Tools::jsonEncode(array(
            'heureClient' => $heureLastNotificationServeur,
            'heureServeur' => $heureLastNotificationServeur,
            'show' => 'false',
            'message' => 'Last notif'
        ));
        die($notif);
    }


    /**
     * Renvoie l'heure du dernier appel
     *
     * @return mixed
     */
    private function getHeureLastCall()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . $this->module->tableName . '` WHERE `type` = "SETUP" ORDER BY `tsms` DESC';
        $req = (Db::getInstance()->getRow($sql));

        return $req;
    }


    /**
     * Est-ce que le dernier appel à été affiché ou est-ce une nouvelle session ?
     *
     * @param $heureLastNotificationCient
     * @param $heureLastNotificationServeur
     * @return bool
     */
    private function newDisplay($heureLastNotificationCient, $heureLastNotificationServeur)
    {
        if ($heureLastNotificationCient == 'null' or
            $heureLastNotificationServeur == $heureLastNotificationCient
        ) {
            return false;
        }
        return true;
    }

    public function ajaxProcessKeyyoComment()
    {
        $req_cm = true;
        $req = true;
        $employee = $this->context->employee;
        $id_contact = Tools::getValue('id_contact');
        $historique_contact = Tools::getValue('historique_contact');

        $comment = array(
            'id_customer' => Tools::getValue('id_customer'),
            'id_employee' => $employee->id,
            'comment' => Tools::getValue('comment'),
            'dref' => Tools::getValue('dref')
        );


        if (!Validate::isCleanHtml($comment['comment']) or
            empty($comment['comment'])
        ) {
            die(Tools::jsonEncode(array(
                'message' => 'Erreur : Commentaires vide ou mauvais format.',
            )));
        }

        if (!$id_contact && empty($historique_contact)) {
            die(Tools::jsonEncode(array(
                'message' => '1',
            )));
        }

        // Ajoute un message dans l'historique des contacts
        if ($comment['id_customer'] && $historique_contact) {
            $req = Db::getInstance()->insert('customer_comments', $comment);
        }

        // Ajoute un message dans la partie SAV si un contact à été choisi
        if ($id_contact != 0) {
            $ct = new CustomerThread();
            $ct->id_shop = (int)$this->context->shop->id;
            $ct->id_contact = (int)$id_contact;
            $ct->id_lang = (int)$this->context->language->id;
            $ct->email = $employee->email;
            $ct->status = 'open';
            $ct->token = Tools::passwdGen(12);
            $ct->add();

            if ($ct->id) {
                $cm = new CustomerMessage();
                $cm->id_customer_thread = (int)$ct->id;
                $cm->message = $comment['comment'];
                $req_cm = $cm->add();
            }
        }

        if (!$req or !$req_cm) {
            die(Tools::jsonEncode(array('message' => '2')));
        } else {
            $this->updateStatus($comment['dref']);
            die(Tools::jsonEncode(array('message' => 'ok')));


        }
    }

    public function whoIsCustomerNumber($number)
    {
        $n = pSQL(substr($number, 2));
        $query = new DbQuery();
        $query->select('a.*, c.*')
            ->from('address', 'a')
            ->leftJoin('customer', 'c', 'a.id_customer = c.id_customer')
            ->where('a.phone LIKE "%' . $n . '" OR a.phone_mobile LIKE "%' . $n . '"')
            ->orderBy('c.date_upd DESC');

        $results = Db::getInstance()->getRow($query);

        return $results;
    }

    public function whoIsNumber($number = '.')
    {
        $query = new DbQuery();

        $query->select('e.lastname, e.firstname')
            ->from('employee', 'e')
            ->where('e.keyyo_caller = "' . $number . '"');

        $result = Db::getInstance()->getRow($query);

        if ($result) {
            return strtoupper($result['lastname']) . ' ' . substr($result['firstname'], 0, 1) . '.';
        } else {
            return $number;
        }

    }

    public function linkRappel($number, $params)
    {
        $customer = $this->whoIsCustomerNumber($number);
        if ($customer) {
            $caller = strtoupper($customer['lastname']) . ' ' . $customer['firstname'] . '</a>';
        } else {
            $caller = '</a>';
        }

        $tokenLiteComment = Tools::getAdminTokenLite('AdminKeyyo');
        $link = $number . ' - <a class="linkRappel" href="' . self::$currentIndex . '&controller=AdminKeyyo&ajax=1&number='
            . $params['caller'] . '&action=RappelNumber&token=' . $tokenLiteComment
            . '&callee=' . $params['callee'] . '&redirectingNumber=' . $params['redirectingnumber']
            . '&tsms=' . $params['tsms']
            . '&dref=' . $params['dref'] . ' ">' . $caller . '<i class="' . $params['dref'] . '"></i> ';
        return $link;
    }

    public function ajaxProcessRappelNumber()
    {
        $notif = array(
            'show' => 'true',
            'heureClient' => '',
            'heureServeur' => Tools::getValue('tsms'),
            'callee' => Tools::getValue('callee'),
            'caller' => Tools::getValue('number'),
            'redirectingNumber' => $this->whoIsNumber(Tools::getValue('redirectingNumber')),
            'linkCustomer' => '',
            'message' => '',
            'callerName' => '',
            'histoMessage' => array(),
            'dateMessage' => '',
            'messageHistorique' => '',
            'id_customer' => '',
            'id_employee' => '',
            'linkPostComment' => '',
            'historique_contact' => '',
            'dref' => Tools::getValue('dref')
        );

        $query = new DbQuery();
        $query->select('a.*, c.*')
            ->from('address', 'a')
            ->leftJoin('customer', 'c', 'a.id_customer = c.id_customer')
            ->where('a.phone LIKE "%' . pSQL(substr($notif['caller'], 2)) . '" OR a.phone_mobile LIKE "%' . pSQL(substr($notif['caller'], 2)) . '"')
            ->orderBy('c.date_upd DESC');

        $results = Db::getInstance()->getRow($query);
        // Si le numéro caller est trouvé
        if ($results) {
            // Création du lien vers la fiche client
            $tokenLite = Tools::getAdminTokenLite('AdminCustomers');
            $link = self::$currentIndex . '&controller=AdminCustomers&id_customer=' . $results['id_customer']
                . '&viewcustomer&token=' . $tokenLite;
            $notif['linkCustomer'] = $link;

            $notif['callerName'] = strtoupper($results['lastname']) . ' ' . ucfirst($results['firstname']);
            $notif['id_customer'] = $results['id_customer'];
            $notif['id_employe'] = $results['id_employee'];

            $query = new DbQuery();
            $query->select('cc.*')
                ->from('customer_comments', 'cc')
                ->where('id_customer = "' . pSQL($results['id_customer']) . '"')
                ->orderBy('cc.date_posted DESC')
                ->limit(5);
            $resultsComments = Db::getInstance()->executeS($query);

            foreach ($resultsComments as $result) {
                $employe = new Employee($result['id_employee']);

                $notif['histoMessage'][] = '<tr><td><p>' . $employe->firstname[0] . '. ' . $employe->lastname
                    . '</p><p>' . $result['date_posted'] . '</p></td><td>' . $result['comment'] . '</td></tr>';
            }

            $notif['messageHistorique'] = 'Merci de rappeler ' . $notif['callerName'] . ' à '
                . date('H:i:s \l\e d-m-Y', substr($notif['heureServeur'], 0, 10))
                . ' Numéro : ' . wordwrap('+' . $notif['caller'], 2, " ", 1);
            $notif['message'] = 'Numéro trouvé.';
        } else {
            $notif['messageHistorique'] = 'Merci de rappeler le ' . wordwrap('+' . $notif['caller'], 2, " ", 1) . ' à '
                . date('H:i:s \l\e d-m-Y', substr($notif['heureServeur'], 0, 10));
            $notif['message'] = 'Numéro non trouvé.';
        }


        $tokenLiteComment = Tools::getAdminTokenLite('AdminKeyyo');
        $notif['linkPostComment'] = self::$currentIndex . '&controller=AdminKeyyo&ajax=1&action=KeyyoComment&token='
            . $tokenLiteComment;

        $notif['dateMessage'] = date('Y-m-d à H:i:s', substr($notif['heureServeur'], 0, 10));
        die(Tools::jsonEncode($notif));
    }

    public function setType($type)
    {
        $r = '';

        if ($type == 'SETUP') {
            $r = '<i class="icon-arrow-left text-success setType"></i>';
        } else if ($type == 'RELEASE') {
            $r = '<i class="icon-arrow-right text-danger setType"></i>';
        } else {
            $r = '<i class="icon-phone text-info setType"></i>';
        }
        return $r;
    }

    public function updateStatus($dref)
    {
        if (Db::getInstance()->getValue('SELECT status FROM ' . _DB_PREFIX_ . $this->table . ' WHERE dref = "' . pSQL($dref) . '"')) {
            // try to disable
            if (!Db::getInstance()->update($this->table, array('status' => 0), 'dref = "' . (pSQL($dref) . '"')))
                $this->_errors[] = Tools::displayError('Error:') . ' ' . mysql_error();
            else return true;
        } else {
            // try to enable
            if (!Db::getInstance()->update($this->table, array('status' => 1), 'dref = "' . pSQL($dref) . '"'))
                $this->_errors[] = Tools::displayError('Error:') . ' ' . mysql_error();
            else return true;
        }
    }

}
