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

class Ps_Languageselector extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ps_languageselector';
        $this->author = 'PrestaShop';
        $this->version = '1.0.6';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Language selector block');
        $this->description = $this->l('Adds a block allowing customers to select a language for your store\'s content.');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:ps_languageselector/ps_languageselector.tpl';
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);

        if (1 < count($languages)) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
            
            return $this->fetch($this->templateFile);
        }

        return false;
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $languages = Language::getLanguages(true, $this->context->shop->id);

        foreach ($languages as &$lang) {
            $lang['name_simple'] = $this->getNameSimple($lang['name']);
        }

        return array(
            'languages' => $languages,
            'current_language' => array(
                'id_lang' => $this->context->language->id,
                'name' => $this->context->language->name,
                'name_simple' => $this->getNameSimple($this->context->language->name)
            )
        );
    }

    private function getNameSimple($name)
    {
        return preg_replace('/\s\(.*\)$/', '', $name);
    }
}
