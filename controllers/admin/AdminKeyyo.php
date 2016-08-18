<?php

/**
 * AdminKeyyo File Doc Comment
 * AdminKeyyo Class Doc Comment
 *
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Dominique <dominique@chez-dominique.fr
 * @copyright 2007-2016 PrestaShop SA / 2011-2016 Dominique
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registred Trademark & Property of PrestaShop SA
 */

require_once(dirname(__FILE__) . '/../../classes/NotificationKeyyoClass.php');

class AdminKeyyoController extends ModuleAdminController
{

    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }
        $this->table = 'notification_keyyo';
        $this->module = 'keyyo';
        $this->className = 'NotificationKeyyoClass';
        $this->identifier = 'id_notification_keyyo';
        $this->lang = false;
        $this->bootstrap = true;
        $this->context = Context::getContext();

        $this->fields_list = array(
            'id_notification_keyyo' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'),
            'account' => array(
                'title' => $this->l('Compte'),
                'class' => 'col-md-1',
            ),
            'caller' => array(
                'title' => $this->l('Du'),
                'class' => 'col-md-1',
            ),
            'callee' => array(
                'title' => $this->l('Pour le'),
                'class' => 'col-md-1',
            ),
            'tsms' => array(
                'title' => $this->l('Heure'),
                'class' => 'col-md-2',
                'callback' => 'formatTime'
            ),
            'type' => array(
                'title' => $this->l('Type'),
                'class' => 'col-md-1',
            ),
        );

        $this->bulk_action = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        parent::__construct();
    }

    public function renderList()
    {
        if (isset($this->_filter) && trim($this->_filter) == '')
            $this->_filter = $this->original_filter;

        $this->addRowAction('view');
        $this->addRowAction('add');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function initPageHeaderToolbar()
    {
        if (!$this->display) {
            $this->page_header_toolbar_btn['new'] = array(
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Add New')
            );
        } else if ($this->display == 'view') {
            $this->page_header_toolbar_btn['back'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back to the list')
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderView()
    {
        $this->tpl_view_vars['notification_keyyo'] = $this->loadObject();
        return parent::renderView();
    }

    public function formatTime($t)
    {
        return date('d-m-Y à H:i:s', substr($t, 0, 10));
    }

}
