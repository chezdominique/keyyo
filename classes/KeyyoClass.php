<?php

class KeyyoClass extends ObjectModel
{
    public $id_keyyo;

    /**
     * @var -Numéro de la ligne Keyyo (format international)
     */
    public $account;

    /**
     * @var -Numéro de l'appelé au format international
     */
    public $callee;

    /**
     * @var -Numéro de l'appelant au format international (valeur par defaut account)
     */
    public $caller;

    public $calle_name;
    public $callref;
    public $dref;
    public $drefreplace;
    public $isacd;
    public $msg;
    public $profil;
    public $record;
    public $redirectingnumber;
    public $sessionid;
    public $version;
    public $tsms;
    public $type;

}