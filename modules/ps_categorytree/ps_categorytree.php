<?php
/*
* 2007-2016 PrestaShop
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
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ps_CategoryTree extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'ps_categorytree';
        $this->tab = 'front_office_features';
        $this->version = '1.0.5';
        $this->author = 'PrestaShop';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->getTranslator()->trans('Category tree links', array(), 'Modules.CategoryTree');
        $this->description = $this->getTranslator()->trans('Adds a block featuring product categories.', array(), 'Modules.CategoryTree');
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('BLOCK_CATEG_MAX_DEPTH', 4)
            && Configuration::updateValue('BLOCK_CATEG_ROOT_CATEGORY', 1)
            && $this->registerHook('displayLeftColumn')
        ;
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('BLOCK_CATEG_MAX_DEPTH') ||
            !Configuration::deleteByName('BLOCK_CATEG_ROOT_CATEGORY')) {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitBlockCategories')) {
            $maxDepth = (int)(Tools::getValue('BLOCK_CATEG_MAX_DEPTH'));
            if ($maxDepth < 0) {
                $output .= $this->displayError($this->getTranslator()->trans('Maximum depth: Invalid number.', array(), 'Admin.Notifications.Error'));
            } else {
                Configuration::updateValue('BLOCK_CATEG_MAX_DEPTH', (int)$maxDepth);
                Configuration::updateValue('BLOCK_CATEG_SORT_WAY', Tools::getValue('BLOCK_CATEG_SORT_WAY'));
                Configuration::updateValue('BLOCK_CATEG_SORT', Tools::getValue('BLOCK_CATEG_SORT'));
                Configuration::updateValue('BLOCK_CATEG_ROOT_CATEGORY', Tools::getValue('BLOCK_CATEG_ROOT_CATEGORY'));

                //$this->_clearBlockcategoriesCache();

                Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&conf=6');
            }
        }
        return $output.$this->renderForm();
    }

    private function getCategories($category)
    {
        $range = '';
        $maxdepth = Configuration::get('BLOCK_CATEG_MAX_DEPTH');
        if (Validate::isLoadedObject($category)) {
            if ($maxdepth > 0) {
                $maxdepth += $category->level_depth;
            }
            $range = 'AND nleft >= '.(int)$category->nleft.' AND nright <= '.(int)$category->nright;
        }

        $resultIds = array();
        $resultParents = array();
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT c.id_parent, c.id_category, cl.name, cl.description, cl.link_rewrite
			FROM `'._DB_PREFIX_.'category` c
			INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = '.(int)$this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
			INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON (cs.`id_category` = c.`id_category` AND cs.`id_shop` = '.(int)$this->context->shop->id.')
			WHERE (c.`active` = 1 OR c.`id_category` = '.(int)Configuration::get('PS_HOME_CATEGORY').')
			AND c.`id_category` != '.(int)Configuration::get('PS_ROOT_CATEGORY').'
			'.((int)$maxdepth != 0 ? ' AND `level_depth` <= '.(int)$maxdepth : '').'
			'.$range.'
			AND c.id_category IN (
				SELECT id_category
				FROM `'._DB_PREFIX_.'category_group`
				WHERE `id_group` IN ('.pSQL(implode(', ', Customer::getGroupsStatic((int)$this->context->customer->id))).')
			)
			ORDER BY `level_depth` ASC, '.(Configuration::get('BLOCK_CATEG_SORT') ? 'cl.`name`' : 'cs.`position`').' '.(Configuration::get('BLOCK_CATEG_SORT_WAY') ? 'DESC' : 'ASC'));
        foreach ($result as &$row) {
            $resultParents[$row['id_parent']][] = &$row;
            $resultIds[$row['id_category']] = &$row;
        }

        return $this->getTree($resultParents, $resultIds, $maxdepth, ($category ? $category->id : null));
    }

    public function getTree($resultParents, $resultIds, $maxDepth, $id_category = null, $currentDepth = 0)
    {
        if (is_null($id_category)) {
            $id_category = $this->context->shop->getCategory();
        }

        $children = [];

        if (isset($resultParents[$id_category]) && count($resultParents[$id_category]) && ($maxDepth == 0 || $currentDepth < $maxDepth)) {
            foreach ($resultParents[$id_category] as $subcat) {
                $children[] = $this->getTree($resultParents, $resultIds, $maxDepth, $subcat['id_category'], $currentDepth + 1);
            }
        }

        if (isset($resultIds[$id_category])) {
            $link = $this->context->link->getCategoryLink($id_category, $resultIds[$id_category]['link_rewrite']);
            $name = $resultIds[$id_category]['name'];
            $desc = $resultIds[$id_category]['description'];
        } else {
            $link = $name = $desc = '';
        }

        return [
            'id' => $id_category,
            'link' => $link,
            'name' => $name,
            'desc'=> $desc,
            'children' => $children
        ];
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->getTranslator()->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'radio',
                        'label' => $this->getTranslator()->trans('Category root', array(), 'Modules.CategoryTree'),
                        'name' => 'BLOCK_CATEG_ROOT_CATEGORY',
                        'hint' => $this->getTranslator()->trans('Select which category is displayed in the block. The current category is the one the visitor is currently browsing.', array(), 'Modules.CategoryTree'),
                        'values' => array(
                            array(
                                'id' => 'home',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('Home category', array(), 'Modules.CategoryTree')
                            ),
                            array(
                                'id' => 'current',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Current category', array(), 'Modules.CategoryTree')
                            ),
                            array(
                                'id' => 'parent',
                                'value' => 2,
                                'label' => $this->getTranslator()->trans('Parent category', array(), 'Modules.CategoryTree')
                            ),
                            array(
                                'id' => 'current_parent',
                                'value' => 3,
                                'label' => $this->getTranslator()->trans('Current category, unless it has no subcategories, in which case the parent category of the current category is used', array(), 'Modules.CategoryTree')
                            ),
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Maximum depth', array(), 'Modules.CategoryTree'),
                        'name' => 'BLOCK_CATEG_MAX_DEPTH',
                        'desc' => $this->getTranslator()->trans('Set the maximum depth of category sublevels displayed in this block (0 = infinite).', array(), 'Modules.CategoryTree'),
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->getTranslator()->trans('Sort', array(), 'Admin.Actions'),
                        'name' => 'BLOCK_CATEG_SORT',
                        'values' => array(
                            array(
                                'id' => 'name',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('By name', array(), 'Admin.Global')
                            ),
                            array(
                                'id' => 'position',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('By position', array(), 'Admin.Global')
                            ),
                        )
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->getTranslator()->trans('Sort order', array(), 'Admin.Actions'),
                        'name' => 'BLOCK_CATEG_SORT_WAY',
                        'values' => array(
                            array(
                                'id' => 'name',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Descending', array(), 'Admin.Global')
                            ),
                            array(
                                'id' => 'position',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('Ascending', array(), 'Admin.Global')
                            ),
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->submit_action = 'submitBlockCategories';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues()
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'BLOCK_CATEG_MAX_DEPTH' => Tools::getValue('BLOCK_CATEG_MAX_DEPTH', Configuration::get('BLOCK_CATEG_MAX_DEPTH')),
            'BLOCK_CATEG_SORT_WAY' => Tools::getValue('BLOCK_CATEG_SORT_WAY', Configuration::get('BLOCK_CATEG_SORT_WAY')),
            'BLOCK_CATEG_SORT' => Tools::getValue('BLOCK_CATEG_SORT', Configuration::get('BLOCK_CATEG_SORT')),
            'BLOCK_CATEG_ROOT_CATEGORY' => Tools::getValue('BLOCK_CATEG_ROOT_CATEGORY', Configuration::get('BLOCK_CATEG_ROOT_CATEGORY'))
        );
    }

    public function setLastVisitedCategory()
    {
        if (method_exists($this->context->controller, 'getCategory') && ($category = $this->context->controller->getCategory())) {
            $this->context->cookie->last_visited_category = $category->id;
        } elseif (method_exists($this->context->controller, 'getProduct') && ($product = $this->context->controller->getProduct())) {
            if (!isset($this->context->cookie->last_visited_category)
                || !Product::idIsOnCategoryId($product->id, array(array('id_category' => $this->context->cookie->last_visited_category)))
                || !Category::inShopStatic($this->context->cookie->last_visited_category, $this->context->shop)
            ) {
                $this->context->cookie->last_visited_category = (int)$product->id_category_default;
            }
        }
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $this->setLastVisitedCategory();
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch('module:ps_categorytree/views/templates/hook/ps_categorytree.tpl');
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $category = new Category((int)Configuration::get('PS_HOME_CATEGORY'), $this->context->language->id);

        if (Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category) {
            $category = new Category($this->context->cookie->last_visited_category, $this->context->language->id);
            if (Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') == 2 && !$category->is_root_category && $category->id_parent) {
                $category = new Category($category->id_parent, $this->context->language->id);
            } elseif (Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') == 3 && !$category->is_root_category && !$category->getSubCategories($category->id, true)) {
                $category = new Category($category->id_parent, $this->context->language->id);
            }
        }

        return [
            'categories' => $this->getCategories($category),
            'currentCategory' => $category->id,
        ];
    }
}
