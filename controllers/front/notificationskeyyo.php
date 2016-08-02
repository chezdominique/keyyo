<?php

class keyyoNotificationskeyyoModuleFrontController extends ModuleFrontController
{
    public $confirmation;

    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign(array(
            'confirmation' => $this->confirmation
        ));
    }

    public function postProcess()
    {
        $values_notification = array();
        $notification_names = array('account', 'caller', 'calle', 'callref', 'type', 'version', 'dref', 'drefreplace',
            'sessionid', 'isacd', 'redirectingnumber', 'tsms');

        foreach ($notification_names as $value) {
            $values_notification[$value] = Tools::htmlentitiesUTF8(Tools::getValue($value));
            $this->errors[] = Tools::displayError($value . ' : ' . $values_notification[$value]);
        }

        if(!Db::getInstance()->insert($this->module->tableName, $values_notification)) {
            $this->errors[] = Tools::displayError('Erreur lors de l\'enregistrement de la requête');
        } else {
            $this->confirmation = 'Enregistrement ok';
        }

        $this->setTemplate('notificationskeyyo.tpl');
    }
}