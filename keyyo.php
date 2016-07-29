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
class Keyyo extends Module
{
    protected $tabName;
    protected $errors = array();
    protected $html = '';

    /* Set default configuration values here */
    protected $config = array(
        'KEYYO_ACCOUNT' => '',
        'KEYYO_NUMBER_FILTER' => ' .-_+'
    );

    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }

        $this->name = 'keyyo';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Dominique';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Module non officiel pour la téléphonie KEYYO');
        $this->description = $this->l('Installe un lien permettant les appels via KEYYO.');
        $this->confirmUninstall = $this->l('Etes vous sur ?');
        $this->tabName = 'Keyyo';
    }

    public function install()
    {
        if (!parent::install() or
            !$this->installConfig() or
            !$this->alterEmployeeTable() or
            !$this->createTabs() or
            !$this->registerHook('displayHeader') or
            !$this->registerHook('displayBackOfficeHeader')
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() or
            !$this->eraseConfig() or
            !$this->eraseTabs() or
            !$this->alterEmployeeTable('remove')
        ) {
            return false;
        }
        return true;
    }

    public function alterEmployeeTable($method = 'add')
    {
        if ($method == 'add') {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'employee ADD `keyyo_account` BIGINT (64) NULL';
        } else {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'employee DROP COLUMN `keyyo_account`';
        }

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
        return true;
    }

    private function installConfig()
    {
        foreach ($this->config as $keyname => $value) {
            Configuration::updateValue($keyname, $value);
        }
        return true;
    }


    private function eraseConfig()
    {
        foreach ($this->config as $keyname => $value) {
            Configuration::deleteByName($keyname);
        }
        return true;
    }

    private function createTabs()
    {
        $tab = new Tab();
        $tab->active = 1;
        $languages = Language::getLanguages(false);
        if (is_array($languages)) {
            foreach ($languages as $language) {
                $tab->name[$language['id_lang']] = $this->tabName;
            }
        }
        $tab->class_name = 'Admin' . Tools::ucfirst($this->name);
        $tab->module = $this->name;
        $tab->id_parent = 0;

        return (bool)$tab->add();
    }

    private function eraseTabs()
    {
        $id_tab = (int)Tab::getIdFromClassName('Admin' . Tools::ucfirst($this->name));
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        $this->postProcess();
        $this->displayForm();

        return $this->html;
    }

    private function postProcess()
    {
        if (Tools::isSubmit('submitUpdate')) {
            $keyyo_account = Tools::getValue('keyyo_account');

            $pattern = '/^33[0-9]{9}$/';
            if (Validate::isString($keyyo_account) &&
                preg_match($pattern, $keyyo_account)
            ) {
                Configuration::updateValue('KEYYO_ACCOUNT', $keyyo_account);
            } else {
                $this->errors[] = 'Le numero n\'est pas au bon format.';
            }

            // Error handling
            if ($this->errors) {
                $this->html .= $this->displayError(implode($this->errors, '<br />'));
            } else {
                $this->html .= $this->displayConfirmation($this->l('Paramètres mis à jour'));
            }
        }
    }

    private function displayForm()
    {
        $this->html .= $this->generateForm();
    }

    private function generateForm()
    {
        $inputs = array();
        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('Compte par défaut KEYYO.'),
            'name' => 'keyyo_account',
            'desc' => 'Veuillez entrer le numéro KEYYO par défaut. (33 suivi des 9 chiffres)',
            'lang' => false
        );
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitUpdate'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'submitUpdate';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfig()
        );
        return $helper->generateForm(array($fields_form));
    }

    private function getConfig()
    {
        return array(
            'keyyo_account' => Configuration::get('KEYYO_ACCOUNT')
        );
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/adminkeyyo.css', 'all');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/adminkeyyo.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/adminkeyyo.js', 'all');
    }
}
