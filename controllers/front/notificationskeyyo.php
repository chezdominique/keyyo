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

        $values_notification = array('account', 'caller', 'calle', 'callref', 'type', 'version', 'dref', 'drefreplace',
            'sessionid', 'isacd', 'redirectingnumber', 'tsms');

        foreach ($values_notification as $value) {
            $values_notification[$value] = Tools::htmlentitiesUTF8(Tools::getValue($value));
            $this->errors[] = Tools::displayError($value . ' : ' . $values_notification[$value]);
        }

        $this->confirmation = 'ok';
        $this->setTemplate('notificationskeyyo.tpl');
    }
}