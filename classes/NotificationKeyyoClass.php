<?php

class NotificationKeyyoClass extends ObjectModel
{
    public $id_notification_keyyo;

    /**
     * @var -Numéro de la ligne Keyyo (format international)
     */
    public $account;

    /**
     * @var -Numéro de l'appelé au format international
     */
    public $callee;

    /**
     * @var -Numéro de l'appelant au format international (valeur par défaut account)
     */
    public $caller;

    /**
     * @var -Nom affiché du numéro appelé
     */
    public $calle_name;

    /**
     * @var -Identifiant de l'appel
     */
    public $callref;

    /**
     * @var -Identifiant du dialogue, présent sur tous les appels et différent du callref
     */
    public $dref;

    /**
     * @var -Identifant du dialogue remplacé (présent dans le cas d'une interception ou d'un transfer)
     */
    public $drefreplace;

    /**
     * @var -Egal à un si l'appel provient d'un numéro d'accueil
     */
    public $isacd;

    /**
     * @var -Contenu du sms à envoyer
     */
    public $msg;

    /**
     * @var -Nom du profil souhaité
     */
    public $profil;

    /**
     * @var -Déclenche l'enregistrement de l'appel et l'envoie sur l'adresse mail associée à la messagerie
     */
    public $record;

    /**
     * @var -Lors d'un appel renvoyé, numéro de la ligne qui éffectue le renvoi
     */
    public $redirectingnumber;

    /**
     * @var -Identifiant de la session commun à l'ensemble des appels générés par un appel entrant sur un numéro d'accueil
     */
    public $sessionid;

    /**
     * @var -Version de l'api de notification
     */
    public $version;

    /**
     * @var -Timestamp en milliseconde de l'évenement
     */
    public $tsms;

    /**
     * @var -Type de notification (SETUP : Initiation de l'appel, CONNECT : Connexion de l'appel, RELEASE : Fin de l'appel)
     */
    public $type;

    public $id_employee;

    public static $definition = array(
        'table' => 'notification_keyyo',
        'primary' => 'id_notification_keyyo',
        'fields' => array(
            'account' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'callee' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'caller' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'calle_name' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'callref' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'dref' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'drefreplace' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'isacd' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'msg' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'profil' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'record' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'redirectingnumber' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'sessionid' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'version' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'tsms' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'type' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'id_employee' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
        ),
    );

    public static function getDref($id)
    {
        $sql = 'SELECT `dref` FROM ' . _DB_PREFIX_ . NotificationKeyyoClass::$definition['table']
            . ' WHERE `id_notification_keyyo` = ' . pSQL($id);

        $req = Db::getInstance()->getValue($sql);

        return $req;
    }
}