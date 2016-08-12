<?php
class Employee extends EmployeeCore
{
    public $keyyo_caller;
    public $keyyo_notification_enabled;
    public $keyyo_notification_numbers;


    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        self::$definition['fields']['keyyo_caller'] = array(
            'type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64
        );
        self::$definition['fields']['keyyo_notification_enabled'] = array(
            'type' => self::TYPE_BOOL, 'validate' => 'isBool'
        );
        self::$definition['fields']['keyyo_notification_numbers'] = array(
            'type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 254
        );


        parent::__construct($id, $id_lang, $id_shop);
    }
    public function getKeyyoCaller()
    {
        return $this->keyyo_caller;
    }

    public function getKeyyoNotificationEnabled()
    {
        return $this->keyyo_notification_enabled;
    }

    public function getKeyyo_notification_numbers()
    {
        return $this->keyyo_notification_numbers;
    }
}
