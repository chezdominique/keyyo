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
//require_once(dirname(__FILE__) . '/../../classes/KeyyoClass.php');


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
                'align' => 'left',
                'callback' => 'makePhoneCall'
            ),
            'phone_mobile' => array(
                'title' => $this->l('Mobile'),
                'align' => 'left',
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

    /**
     * Création de l'url pour l'appel ajax vers le client
     * @param $number
     * @param $params
     * @return string
     */
    public function makePhoneCall($number, $params)
    {
        $phoneNumber = $this->sanityzePhoneNumber($number);
        $ln = strlen($phoneNumber);
        $display_message = ($ln != 10 && $ln > 0) ? '<i class="icon-warning text-danger"></i>' : '';

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

    protected function getKeyyoCaller()
    {

        $keyyo_account = $this->context->employee->getKeyyoCaller();

        if (!empty($keyyo_account)) {
            $keyyo_number = $keyyo_account;
        } else {
            return false;
        }
        return $keyyo_number;
    }

    public function ajaxProcessKeyyoCall()
    {
//        $account = '33430966096';
//        $passsip = 'vLPPi6L5Lv';
        
        $account = $this->getKeyyoCaller();
        $callee = Tools::getValue('CALLEE');
        $calle_name = Tools::getValue('CALLE_NAME');

        if (!$account) {
            $return = Tools::jsonEncode(array('msg' => 'Veuillez configurer votre numéro de compte KEYYO.'));
            die($return);
        }

        if (!$callee || !$calle_name) {
            $return = Tools::jsonEncode(array('msg' => 'Il manque une information pour composer le numéro.'));
            die($return);
        } else {
            $keyyo_link = 'https://ssl.keyyo.com/makecall.html?ACCOUNT=' . $account;
            $keyyo_link .= '&CALLEE=' . $callee;
            $keyyo_link .= '&CALLE_NAME=' . $calle_name;

//            $ch = curl_init();
//
//            curl_setopt($ch, CURLOPT_URL, $keyyo_link);
//            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            curl_setopt($ch, CURLOPT_USERPWD, $account . ":" . $passsip);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//
//            $fp = curl_exec($ch);
//            curl_close($ch);
//
//            die($fp);

            $fp = fopen($keyyo_link, 'r');
            if ($fp) {
                $return = Tools::jsonEncode(array('msg' => 'Appel du ' . $callee . ' en cours.'));
                die($return);
            } else {
                $return = Tools::jsonEncode(array('msg' => 'Problème lors de l\'appel.'));
                die($return);
            }
        }
    }

    private function sanityzePhoneNumber($number)
    {
        $pattern = str_split(Configuration::get('KEYYO_NUMBER_FILTER'));
        $number = str_replace($pattern, '', $number);
        if (substr($number, 0, 1) != '0') {
            $number = '0' . $number;
        }

        return $number;
    }
}
