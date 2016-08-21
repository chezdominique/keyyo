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
    // URL de notification à entrée dans KEYYO manager
    // http://www.l-et-sens.com/module/keyyo/notificationskeyyo?account=_ACCOUNT_&caller=_CALLER_&callee=_CALLEE_&type=_N_TYPE_&callref=_CALLREF_&version=_N_VERSION_&dref=_DREF_&drefreplace=_DREF_REPLACE_&sessionid=_SESSION_ID_&isacd=_IS_ACD_&redirectingnumber=_REDIRECTING_NUMBER_&tsms=_TSMS_
    protected $tabName;
    protected $errors = array();
    protected $html = '';
    public $numbers_names = array();

    /* Set default configuration values here */
    protected $config = array(
        'KEYYO_ACCOUNT' => '', // Compte par defaut KEYYO
        'KEYYO_NUMBER_FILTER' => ' .-_+', // Supprime les caractères suivant des numéros de téléphone
        'KEYYO_URL' => 'https://ssl.keyyo.com/makecall.html'
    );

    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }

        $this->numbers_names = array(
            '33430966600' => 'Standard L&Sens',
            '33430966996' => 'DclicBIO',
            '33430966096' => 'ISONAUTIQUE',
        );
        $this->name = 'keyyo';
        $this->tableName = 'notification_keyyo';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Dominique';
        $this->need_instance = 0;
        $this->controllers = array('notificationskeyyo');

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Module pour la téléphonie KEYYO');
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
            !$this->registerHook('displayBackOfficeHeader') or
            !$this->registerHook('displayBackOfficeTop') or
            !$this->registerHook('displayLeftColumn') or
            !$this->createNotificationKeyyoTable()
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
            !$this->alterEmployeeTable('remove') or
            !$this->removeNotificationKeyyoTable()
        ) {
            return false;
        }
        return true;
    }

    public function alterEmployeeTable($method = 'add')
    {
        return true;
        if ($method == 'add') {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'employee 
            ADD `keyyo_caller` VARCHAR (255) NULL,
            ADD `keyyo_notification_enabled` BOOL DEFAULT FALSE,
            ADD `keyyo_notification_numbers` VARCHAR (255) NULL
            ';
        } else {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'employee
            DROP COLUMN `keyyo_caller`,
            DROP COLUMN `keyyo_notification_enabled`,
            DROP COLUMN `keyyo_notification_numbers`
            ';
        }

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
        return true;
    }

    public function createNotificationKeyyoTable()
    {
        $sql = 'CREATE TABLE `' . _DB_PREFIX_ . $this->tableName . '` (
            `id_notification_keyyo` INT (12) NOT NULL AUTO_INCREMENT,
            `account` VARCHAR (32) NULL,
            `callee` VARCHAR (32) NULL,
            `caller` VARCHAR (32) NULL,
            `calle_name` VARCHAR (64) NULL,
            `callref` VARCHAR (64) NULL,
            `dref` VARCHAR (32) NULL,
            `drefreplace` VARCHAR (32) NULL,
            `isacd` CHAR (2) NULL,
            `msg` VARCHAR (64) NULL,
            `profil` VARCHAR (64) NULL,
            `record` CHAR (2) NULL,
            `redirectingnumber` VARCHAR (32) NULL,
            `sessionid` VARCHAR (64) NULL,
            `version` VARCHAR (32) NULL,
            `tsms` VARCHAR (32) NULL,
            `type` VARCHAR (32) NULL,
            `status` BOOL NULL,
            PRIMARY KEY (`id_notification_keyyo`)
        ) ENGINE = ' . _MYSQL_ENGINE_;

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
        return true;
    }

    private function removeNotificationKeyyoTable()
    {
        if (!Db::getInstance()->execute('DROP TABLE `' . _DB_PREFIX_ . $this->tableName . '`')) {
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
        $keyyo_caller = $this->context->employee->keyyo_caller;
        if (!$keyyo_caller) {
            $this->html .= $this->displayError($this->l('Veuillez configurer votre numéro d\'appelé
            dans l\'onglet Administration>Employés '));
        } else {
            $this->html .= $this->displayConfirmation($this->l('Votre numéro de compte KEYYO est le ' . $keyyo_caller));
        }

        $this->postProcess();
        $this->displayForm();

        return $this->html;
    }

    private function postProcess()
    {
        if (Tools::isSubmit('submitConfiguration')) {
            $keyyo_url = trim(Tools::getValue('keyyo_url'));
            if (Validate::isString($keyyo_url)) {
                Configuration::updateValue('KEYYO_URL', $keyyo_url);
            } else {
                $this->errors[] = $this->l('Le format de l\'adresse n\'est pas correct');
            }
        }

        // Error handling
        if ($this->errors) {
            $this->html .= $this->displayError(implode($this->errors, '<br />'));
        } else {
            $this->html .= $this->displayConfirmation($this->l('Paramètres mis à jour'));
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
            'label' => $this->l('URL vers le serveur KEYYO'),
            'name' => 'keyyo_url',
            'desc' => $this->l('Veuillez entrer l\'url vers le serveur Keyyo ( https://ssl.keyyo.com/makecall.html ou
            http://www.chez-dominique.fr/makecall.php )'),
            'lang' => false
        );
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Paramètres'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitConfiguration'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'submitConfiguration';
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
            'keyyo_url' => Configuration::get('KEYYO_URL')
        );
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJquery();
        $this->context->controller->addJS($this->_path . 'views/js/jquery.cookie.js', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/remodal.js', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/adminkeyyo.js', 'all');
        if ($this->context->employee->keyyo_notification_enabled) {
            $this->context->controller->addCSS($this->_path . 'views/css/bootstrap.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/remodal.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/remodal-default-theme.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/adminkeyyo.css', 'all');


            $id_employee = $this->context->employee->id;
            $this->smarty->assign(array(
                'contacts' => Contact::getContacts($this->context->language->id),
            ));

            $modal = $this->display(__FILE__, 'modalKeyyo.tpl');
            return $modal;
        }
        return false;
    }


    public function hookDisplayLeftColumn($params)
    {
//            Lien pour la notification de KEYYO, uniquement pour test

//            $lien = '?account=33123456789&caller=33987654321&calle=123456987&type=SETUP';
//            $this->context->smarty->assign(array('lien' => $lien));
//            return $this->display(__FILE__, 'notificationsKeyyo.tpl');
    }


    public function hookDisplayBackOfficeTop()
    {
        if ($this->context->controller->tabAccess['class_name'] == 'AdminKeyyo') {
            if ($this->context->employee->keyyo_notification_enabled) {
//            $heureLastCall = '1471081437805';
                $heureLastCall = 'null';
                $checkbox = '<button id="checkboxAppelsKeyyo" class="list-action-enable action-disabled" url="';
                $checkbox .= Context::getContext()->link->getAdminLink('AdminKeyyo') . '&ajax=1&action=AffichageAppels"';
                $checkbox .= 'title="disabled" heureLastNotif="' . $heureLastCall . '"><i id="notifKeyyoCheck" ';
                $checkbox .= 'class="icon-check hidden"></i><i id="notifKeyyoRemove" class="icon-remove">';
                $checkbox .= '</i>  Notification d\'appels</button>';

                $btnRappelModal ='  <button id="rappelModal" type="button" class="btn btn-info">Rappel Fenêtre</button>';

                return $checkbox . $btnRappelModal;
            }
        }

        return false;
    }

}
