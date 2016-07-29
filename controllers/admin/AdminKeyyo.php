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
class AdminKeyyoController extends ModuleAdminController
{
    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }
        $this->module = 'keyyo';
        $this->className = 'AdminKeyyoController';
        $this->lang = false;
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->required_fields = array('id_customer', 'phone');
        $this->table = 'customer';
        $this->explicitSelect = false;
        $this->addRowAction('afficher');
        $this->addCSS(_PS_MODULE_DIR_ . 'keyyo/views/css/adminkeyyo.css');
        $this->addJquery();
        $this->addJS(_PS_MODULE_DIR_ . 'keyyo/views/js/adminkeyyo.js');
        $this->list_no_link = true;

        $this->fields_list = array(
            'id_customer' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'),
            'firstname' => array(
                'title' => $this->l('Prénom'),
                'filter_key' => 'a!firstname'),
            'lastname' => array(
                'title' => $this->l('Nom'),
                'filter_key' => 'a!lastname'),
            'address1' => array(
                'title' => $this->l('Adresse')),
            'postcode' => array(
                'title' => $this->l('Code postal'),
                'align' => 'right'),
            'city' => array(
                'title' => $this->l('Ville')),
            'phone' => array(
                'title' => $this->l('Téléphone'),
                'align' => 'center',
                'callback' => 'makePhoneCall'
            ),
            'phone_mobile' => array(
                'title' => $this->l('Mobile'),
                'align' => 'center',
                'callback' => 'makePhoneCall'
            )
        );

        parent::__construct();

        $this->_select =
            'a.id_customer, a.firstname, a.lastname, ad.address1, ad.postcode, ad.city, ad.phone, ad.phone_mobile';
        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'address` ad ON a.id_customer = ad.id_customer
        ';
    }

    public function makePhoneCall($number, $params)
    {
        $phoneNumber = $this->sanityzePhoneNumber($number);

        $keyyo_link = '<a href="' . Context::getContext()->link->getAdminLink('AdminKeyyo') . '&ajax=1&action=KeyyoCall';
        $keyyo_link .= '&CALLE=' . $phoneNumber;
        $keyyo_link .= '&CALLE_NAME=' . $params['lastname'] . '_' . $params['firstname'];
        $keyyo_link .= '" class="keyyo_link">' . $phoneNumber . '</a>';

        return $keyyo_link;
    }

    public function displayAfficherLink($token, $id)
    {
        $tokenLite = Tools::getAdminTokenLite('AdminCustomers');
        $link =
            self::$currentIndex . '&controller=AdminCustomers&id_customer=' . $id . '&viewcustomer&token=' . $tokenLite;
        return '<a href="' . $link . '" class="icon-search-plus"> Afficher</a>';
    }

    protected function getKeyyoAccount()
    {
        $sql = 'SELECT keyyo_account FROM `' . _DB_PREFIX_ . 'employee` 
        WHERE id_employee = ' . $this->context->employee->id;
        $keyyo_account = Db::getInstance()->getRow($sql);

        if (!empty($keyyo_account['keyyo_account'])) {
            $keyyo_number = $keyyo_account['keyyo_account'];
        } else {
            $keyyo_number = Configuration::get('KEYYO_ACCOUNT');
        }
        return $keyyo_number;
    }

    public function ajaxProcessKeyyoCall()
    {
        $account = $this->getKeyyoAccount();
        $calle = Tools::getValue('CALLE');
        $calle_name = Tools::getValue('CALLE_NAME');

        if (!$account) {
            $return = Tools::jsonEncode(array('msg' => 'Veuillez configurer votre numéro de compte KEYYO.'));
            die($return);
        }

        if (!$calle || !$calle_name) {
            $return = Tools::jsonEncode(array('msg' => 'Il manque une information pour composer le numéro.'));
            die($return);
        } else {
            $keyyo_link = 'https://ssl.keyyo.com/makecall.html?ACCOUNT=' . $account;
            $keyyo_link .= '&CALLE=' . $calle . '&CALLE_NAME=' . $calle_name;

            $fp = fopen($keyyo_link, 'r');

            if ($fp) {
                $return = Tools::jsonEncode(array('msg' => 'Appel du ' . $calle . ' en cours.'));
                die($return);
            } else {
                $return = Tools::jsonEncode(array('msg' => 'Problème lors de l\'appel.'));
                die($return);
            }
        }
    }

    private function sanityzePhoneNumber($number)
    {
        return $number;
    }
}
