<?php

class AdminLinkWidgetController extends ModuleAdminController
{
    public $className = 'LinkBlock';
    private $name;
    private $repository;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
        $this->meta_title = $this->module->getTranslator()->trans('Link Widget', array(), 'Modules.LinkList');

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        $this->name = 'LinkWidget';

        $this->repository = new LinkBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );
    }

    public function init()
    {
        if (Tools::isSubmit('edit'.$this->className)) {
            $this->display = 'edit';
        } elseif (Tools::isSubmit('addLinkBlock')) {
            $this->display = 'add';
        }

        parent::init();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->className)) {

            if (!$this->manageLinkList()) {
                return false;
            }

            $hook_name = Hook::getNameById(Tools::getValue('id_hook'));
            if (!Hook::isModuleRegisteredOnHook($this->module, $hook_name, $this->context->shop->id)) {
                Hook::registerHook($this->module, $hook_name);
            }

            $this->module->_clearCache($this->module->templateFile);

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        } elseif (Tools::isSubmit('delete'.$this->className)) {

            if (!$this->deleteLinkList()) {
                return false;
            }

            $this->module->_clearCache($this->module->templateFile);

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        }

        return parent::postProcess();
    }

    public function renderView()
    {
        $title = $this->module->getTranslator()->trans('Link block configuration', array(), 'Modules.LinkList');

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $title,
                'icon' => 'icon-list-alt'
            ),
            'input' => array(
                array(
                    'type' => 'link_blocks',
                    'label' => $this->module->getTranslator()->trans('Link Blocks', array(), 'Modules.LinkList'),
                    'name' => 'link_blocks',
                    'values' => $this->repository->getCMSBlocksSortedByHook(),
                ),
            ),
            'buttons' => array(
                'newBlock' => array(
                    'title' => $this->module->getTranslator()->trans('New block', array(), 'Modules.LinkList'),
                    'href' => $this->context->link->getAdminLink('Admin'.$this->name).'&amp;addLinkBlock',
                    'class' => 'pull-right',
                    'icon' => 'process-icon-new'
                ),
            ),
        );

        $this->getLanguages();


        $helper = $this->buildHelper();
        $helper->submit_action = '';
        $helper->title = $title;

        $helper->fields_value = $this->fields_value;

        return $helper->generateForm($this->fields_form);
    }

    public function renderForm()
    {
        $block = new LinkBlock((int)Tools::getValue('id_link_block'));

        $this->fields_form[0]['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => isset($block) ? $this->module->getTranslator()->trans('Edit the link block.', array(), 'Modules.LinkList') : $this->module->getTranslator()->trans('New link block', array(), 'Modules.LinkList'),
                'icon' => isset($block) ? 'icon-edit' : 'icon-plus-square'
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_link_block',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->getTranslator()->trans('Name of the link block', array(), 'Modules.LinkList'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->module->getTranslator()->trans('Hook', array(), 'Admin.Global'),
                    'name' => 'id_hook',
                    'class' => 'input-lg',
                    'options' => array(
                        'query' => $this->repository->getDisplayHooksForHelper(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'cms_pages',
                    'label' => $this->module->getTranslator()->trans('Content pages', array(), 'Modules.LinkList'),
                    'name' => 'cms[]',
                    'values' => $this->repository->getCmsPages(),
                    'desc' => $this->module->getTranslator()->trans('Please mark every page that you want to display in this block.', array(), 'Modules.LinkList')
                ),
                array(
                    'type' => 'product_pages',
                    'label' => $this->module->getTranslator()->trans('Product pages', array(), 'Modules.LinkList'),
                    'name' => 'product[]',
                    'values' => $this->repository->getProductPages(),
                    'desc' => $this->module->getTranslator()->trans('Please mark every page that you want to display in this block.', array(), 'Modules.LinkList')
                ),
                array(
                    'type' => 'static_pages',
                    'label' => $this->module->getTranslator()->trans('Static content', array(), 'Modules.LinkList'),
                    'name' => 'static[]',
                    'values' => $this->repository->getStaticPages(),
                    'desc' => $this->module->getTranslator()->trans('Please mark every page that you want to display in this block.', array(), 'Modules.LinkList')
                ),
            ),
            'buttons' => array(
                'cancelBlock' => array(
                    'title' => $this->module->getTranslator()->trans('Cancel', array(), 'Admin.Actions'),
                    'href' => (Tools::safeOutput(Tools::getValue('back', false)))
                                ?: $this->context->link->getAdminLink('Admin'.$this->name),
                    'icon' => 'process-icon-cancel'
                )
            ),
            'submit' => array(
                'name' => 'submit'.$this->className,
                'title' => $this->module->getTranslator()->trans('Save', array(), 'Admin.Actions'),
            )
        );

        if ($id_hook = Tools::getValue('id_hook')) {
            $block->id_hook = (int)$id_hook;
        }

        if (Tools::getValue('name')) {
            $block->name = Tools::getValue('name');
        }

        $helper = $this->buildHelper();
        if (isset($id_link_block)) {
            $helper->currentIndex = AdminController::$currentIndex.'&id_link_block='.$id_link_block;
            $helper->submit_action = 'edit'.$this->className;
        } else {
            $helper->submit_action = 'addLinkBlock';
        }

        $helper->fields_value = (array)$block;

        return $helper->generateForm($this->fields_form);
    }

    protected function buildHelper()
    {
        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->override_folder = 'linkwidget/';
        $helper->identifier = $this->className;
        $helper->token = Tools::getAdminTokenLite('Admin'.$this->name);
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->context->link->getAdminLink('Admin'.$this->name);
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->module->getTranslator()->trans('Themes', array(), 'Modules.LinkList');
        $this->toolbar_title[] = $this->module->getTranslator()->trans('Link Widget', array(), 'Modules.LinkList');
    }

    public function setMedia()
    {
        parent::setMedia();

        $this->addJqueryPlugin('tablednd');
        $this->addJS(_PS_JS_DIR_.'admin/dnd.js');
    }

    private function manageLinkList()
    {
        $success = true;

        $id_link_block = (int) Tools::getValue('id_link_block');
        $id_hook = (int) Tools::getValue('id_hook');

        if (!empty($id_hook)) {

            $content = '';

            $cms = Tools::getValue('cms');
            $content .= '{"cms":[' . (empty($cms) ? 'false': '"' . implode('","', array_map('intval', $cms)) . '"') . '],';

            $product = Tools::getValue('product');
            $content .= '"product":[' . (empty($product) ? 'false': '"' . implode('","', array_map('bqSQL', $product)) . '"') . '],';

            $static = Tools::getValue('static');
            $content .= '"static":[' . (empty($static) ? 'false': '"' . implode('","', array_map('bqSQL', $static)) . '"') . ']}';

            if (empty($id_link_block)) {
                $query = 'INSERT INTO `'._DB_PREFIX_.'link_block` (`id_hook`, `position`, `content`) VALUES
                (' . $id_hook.', 1, \''.$content. '\')';

                $success &= Db::getInstance()->execute($query);
                $id_link_block = (int) Db::getInstance()->Insert_ID();

                if (!empty($success) && !empty($id_link_block)) {

                    $languages = Language::getLanguages(true, Context::getContext()->shop->id);

                    if (!empty($languages)) {
                        $query = 'INSERT INTO `' . _DB_PREFIX_ . 'link_block_lang` (`id_link_block`, `id_lang`, `name`) VALUES ';

                        foreach ($languages as $lang) {
                            $query .= '(' . $id_link_block . ',' . (int)$lang['id_lang'] . ',\'' . bqSQL(Tools::getValue('name_'.(int)$lang['id_lang'])) . '\'),';
                        }

                        $success &= Db::getInstance()->execute(rtrim($query, ','));
                    }
                }

            } else {
                $query = 'UPDATE `'._DB_PREFIX_.'link_block` 
                    SET `content` = \''.$content.'\', `id_hook` = '.$id_hook.' 
                    WHERE `id_link_block` = '.$id_link_block;
                $success &= Db::getInstance()->execute($query);

                if (!empty($success) && !empty($id_link_block)) {

                    $languages = Language::getLanguages(true, Context::getContext()->shop->id);

                    if (!empty($languages)) {
                        foreach ($languages as $lang) {
                            $query = 'UPDATE `' . _DB_PREFIX_ . 'link_block_lang` 
                                SET `name` = \''.bqSQL(Tools::getValue('name_'.(int)$lang['id_lang'])).'\' 
                                WHERE `id_link_block` = '.$id_link_block.' AND `id_lang` = '.(int)$lang['id_lang'];
                            $success &= Db::getInstance()->execute($query);
                        }
                    }
                }
            }
        } else {
            $success = false;
        }

        return $success;
    }

    private function deleteLinkList()
    {
        $success = false;

        $id_link_block = (int) Tools::getValue('id_link_block');

        if (!empty($id_link_block)) {
            $success &= Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'link_block` WHERE `id_link_block` = '.$id_link_block);

            if ($success) {
                $success &= Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'link_block_lang` WHERE `id_link_block` = '.$id_link_block);
            }
        }

        return $success;
    }
}
