<?php

class keyyoNotificationskeyyoModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $t = Tools::getValue('ACCOUNT', 'null');
        $this->errors[] = Tools::displayError('Erreur = ' . $t);
        $this->setTemplate('notificationskeyyo.tpl');
    }
}