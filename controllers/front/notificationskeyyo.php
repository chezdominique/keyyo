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

//        $url['url'] = $this->context->smarty->tpl_vars['come_from']->value;
//        if (!Db::getInstance()->insert($this->module->tableName, $url ));

        $values_notification = array();
        $notification_names = array('account', 'caller', 'callee', 'callref', 'type', 'version', 'dref', 'drefreplace',
            'sessionid', 'isacd', 'redirectingnumber', 'tsms');


        foreach ($notification_names as $name) {

            $values_notification[$name] = Tools::getValue($name);
            $this->errors[] = Tools::displayError($name . ' : ' . $values_notification[$name]);
        }

        if(!Db::getInstance()->insert($this->module->tableName, $values_notification)) {
            $this->errors[] = Tools::displayError('Erreur lors de l\'enregistrement de la requête');
        } else {
            $this->confirmation = 'Enregistrement ok';
        }

        $this->setTemplate('notificationskeyyo.tpl');
    }
}
