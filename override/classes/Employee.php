<?php

class Employee extends EmployeeCore
{
    public $keyyo_account;

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        self::$definition['fields']['keyyo_account'] = array(
            'type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 15
        );
        parent::__construct($id, $id_lang, $id_shop);
    }
}
