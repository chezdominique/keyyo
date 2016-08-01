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

}