<?php
/*
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ps_Customeraccountlinks extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ps_customeraccountlinks';
        $this->author = 'PrestaShop';
        $this->version = '1.0.4';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('My Account block');
        $this->description = $this->l('Displays a block with links relative to a user\'s account.');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:ps_customeraccountlinks/ps_customeraccountlinks.tpl';
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionModuleRegisterHookAfter')
            && $this->registerHook('actionModuleUnRegisterHookAfter')
            && $this->registerHook('displayFooter')
        ;
    }

    public function uninstall()
    {
        return (parent::uninstall()
            && $this->removeMyAccountBlockHook());
    }

    public function hookActionModuleUnRegisterHookAfter($params)
    {
        if ('displayMyAccountBlock' === $params['hook_name']) {
            $this->_clearCache('*');
        }
    }

    public function hookActionModuleRegisterHookAfter($params)
    {
        if ($params['hook_name'] == 'displayMyAccountBlock') {
            $this->_clearCache('*');
        }
    }

    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        parent::_clearCache($this->templateFile);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('ps_customeraccountlinks'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('ps_customeraccountlinks'));
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $link = $this->context->link;

        $my_account_urls = array(
            0 => array(
                'title' => $this->l('Orders'),
                'url' => $link->getPageLink('history', true),
            ),
            2 => array(
                'title' => $this->l('Credit slips'),
                'url' => $link->getPageLink('order-slip', true),
            ),
            3 => array(
                'title' => $this->l('Addresses'),
                'url' => $link->getPageLink('addresses', true),
            ),
            4 => array(
                'title' => $this->l('Personal info'),
                'url' => $link->getPageLink('identity', true),
            ),
        );

        if ((int)Configuration::get('PS_ORDER_RETURN')) {
            $my_account_urls[1] = array(
                'title' => $this->l('Merchandise returns'),
                'url' => $link->getPageLink('order-follow', true),
            );
        }

        if (CartRule::isFeatureActive()) {
            $my_account_urls[5] = array(
                'title' => $this->l('Vouchers'),
                'url' => $link->getPageLink('discount', true),
            );
        }

        // Sort Account links base in his title, keeping the keys
        asort($my_account_urls);

        return array(
            'my_account_urls' => $my_account_urls,
            'logout_url' => $link->getPageLink('index', true, null, "mylogout"),
        );
    }
}
