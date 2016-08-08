<?php







class Employee extends EmployeeCore



{



    public $keyyo_caller;







    public function __construct($id = null, $id_lang = null, $id_shop = null)



    {



        self::$definition['fields']['keyyo_caller'] = array(



            'type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64



        );



        parent::__construct($id, $id_lang, $id_shop);



    }







    public function getKeyyoCaller()



    {



        return $this->keyyo_caller;



    }







}



