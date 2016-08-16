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
        $this->addJquery();
        $this->addJS(_PS_MODULE_DIR_ . 'keyyo/views/js/jquery.cookie.js');

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
        $phoneNumber = $this->sanitizePhoneNumber($number);
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

    public function ajaxProcessKeyyoCall()
    {

        $keyyo_url = Configuration::get('KEYYO_URL');
        $account = $this->context->employee->getKeyyoCaller();
        $callee = Validate::isString(Tools::getValue('CALLEE')) ? Tools::getValue('CALLEE') : '';
        $calle_name = Validate::isString(Tools::getValue('CALLE_NAME')) ? Tools::getValue('CALLE_NAME') : '';

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
        // Verifier si l'employé veut les notifications et sur quels numeros
        // Verifier si il y a une heure de demande et quel heure si pas d'heure, renvoyer desuite l'heure actuelle
        // si la demande est plus ancienne que

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
            'linkPostComment' => ''
        );

        // Est-ce que l'employé peut afficher les notifications ?
        if (!$this->context->employee->keyyo_notification_enabled) {
            $notif['message'] = $this->l('Vous ne pouvez pas afficher les notifications');
            $notif['show'] = 'false';
            die(Tools::jsonEncode($notif));
        }
        $lastCall = $this->getHeureLastCall();
        $notif['caller'] = $lastCall['caller'];
        $notif['callee'] = $lastCall['callee'];
        $notif['redirectingNumber'] = ($lastCall['redirectingnumber']) ? $lastCall['redirectingnumber'] : '';

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

                    $notif['messageHistorique'] = 'Appel de ' . $notif['callerName'] . ' à '
                        . date('H:i:s \l\e d-m-Y', substr($notif['heureServeur'], 0, 10))
                        . ' Numéro : ' . wordwrap('+'.$notif['caller'], 2, " ", 1);
                    $notif['message'] = 'Numéro trouvé.';
                } else {
                    $notif['messageHistorique'] = 'Appel du : ' . wordwrap('+'.$notif['caller'], 2, " ", 1).' à '
                        . date('H:i:s \l\e d-m-Y', substr($notif['heureServeur'], 0, 10));
                    $notif['message'] = 'Numéro non trouvé.';
                }

                $notif['redirectingNumber'] = (strlen($notif['redirectingNumber']) % 2)
                    ? '+' . $notif['redirectingNumber']
                    : $notif['redirectingNumber'];

                $notif['callee'] = (strlen($notif['callee']) % 2) ? '+' . $notif['callee'] : $notif['callee'];
                $notif['caller'] = (strlen($notif['caller']) % 2) ? '+' . $notif['caller'] : $notif['caller'];

                $notif['redirectingNumber'] = wordwrap($notif['redirectingNumber'], 2, " ", 1);
                $notif['caller'] = wordwrap($notif['caller'], 2, " ", 1);
                $notif['callee'] = wordwrap($notif['callee'], 2, " ", 1);
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

        $comment = array(
            'id_customer' => Tools::getValue('id_customer'),
            'id_employee' => $employee->id,
            'comment' => Tools::getValue('comment'),
        );


        if (!Validate::isCleanHtml($comment['comment']) or
            empty($comment['comment'])
        ) {
            die(Tools::jsonEncode(array(
                'message' => 'Erreur : Commentaires vide ou mauvais format.',
            )));
        }

        if (!$id_contact && !$comment['id_customer']) {
            die(Tools::jsonEncode(array(
                'message' => 'Erreur : Pas de destinataire choisi.',
            )));
        }


        // Ajoute un message dans l'historique des contacts
        if ($comment['id_customer']) {
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
                $cm->id_customer_thread = $ct->id;
                $cm->message = $comment['comment'];
                $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                $cm->user_agent = $_SERVER['HTTP_USER_AGENT'];
                $req_cm = $cm->add();
            }
        }


        if (!$req or !$req_cm) {
            die(Tools::jsonEncode(array('message' => 'Il y a eu une erreur')));
        } else {
            die(Tools::jsonEncode(array('message' => 'Commentaire ajouté')));
        }


    }


}
