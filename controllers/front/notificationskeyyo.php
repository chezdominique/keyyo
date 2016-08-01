<?php

class keyyoNotificationskeyyoModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $values_notification = array('account', 'caller', 'calle', 'callref', 'type', 'version', 'dref', 'drefreplace',
            'sessionid', 'isacd', 'redirectingnumber', 'tsms');

        foreach ($values_notification as $value) {
            $values_notification[$value] = Tools::getValue($value, null);
            $this->errors[] = Tools::displayError($value . ' : ' . $values_notification[$value]);
        }

        $this->setTemplate('notificationskeyyo.tpl');
    }
}