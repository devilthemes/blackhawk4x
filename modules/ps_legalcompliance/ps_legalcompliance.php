<?php
/**
 * 2007-2016 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 * @author     PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2016 PrestaShop SA
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/* Namespaces used in this module */
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use PrestaShop\PrestaShop\Core\Foundation\Filesystem\FileSystem;
use PrestaShop\PrestaShop\Core\Email\EmailLister;
use PrestaShop\PrestaShop\Core\Checkout\TermsAndConditions;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

/* Include required entities */
include_once dirname(__FILE__).'/entities/AeucCMSRoleEmailEntity.php';
include_once dirname(__FILE__).'/entities/AeucEmailEntity.php';

class Ps_LegalCompliance extends Module
{
    /* Class members */
    protected $config_form = false;
    protected $entity_manager;
    protected $filesystem;
    protected $emails;
    protected $_errors;
    protected $_warnings;

    /* Constants used for LEGAL/CMS Management */
    const LEGAL_NO_ASSOC = 'NO_ASSOC';
    const LEGAL_NOTICE = 'LEGAL_NOTICE';
    const LEGAL_CONDITIONS = 'LEGAL_CONDITIONS';
    const LEGAL_REVOCATION = 'LEGAL_REVOCATION';
    const LEGAL_REVOCATION_FORM = 'LEGAL_REVOCATION_FORM';
    const LEGAL_PRIVACY = 'LEGAL_PRIVACY';
    const LEGAL_ENVIRONMENTAL = 'LEGAL_ENVIRONMENTAL';
    const LEGAL_SHIP_PAY = 'LEGAL_SHIP_PAY';

    public function __construct(EntityManager $entity_manager,
                                FileSystem $fs,
                                EmailLister $email)
    {
        $this->name = 'ps_legalcompliance';
        $this->tab = 'administration';
        $this->version = '1.1.6';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        /* Register dependencies to module */
        $this->entity_manager = $entity_manager;
        $this->filesystem = $fs;
        $this->emails = $email;

        $this->displayName = $this->l('Legal Compliance');
        $this->description = $this->l('This module helps merchants comply with applicable e-commerce laws.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        /* Init errors var */
        $this->_errors = array();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update.
     */
    public function install()
    {
        $return = parent::install() &&
            $this->loadTables() &&
            $this->installHooks() &&
            $this->registerModulesBackwardCompatHook() &&
            $this->registerHook('header') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('displayCheckoutSubtotalDetails') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('displayFooterAfter') &&
            $this->registerHook('actionEmailAddAfterContent') &&
            $this->registerHook('advancedPaymentOptions') &&
            $this->registerHook('displayCartTotalPriceLabel') &&
            $this->registerHook('displayCMSPrintButton') &&
            $this->registerHook('displayCMSDisputeInformation') &&
            $this->registerHook('termsAndConditions') &&
            $this->registerHook('displayOverrideTemplate') &&
            $this->registerHook('displayCheckoutSummaryTop') &&
            $this->registerHook('sendMailAlterTemplateVars') &&
            $this->registerHook('displayReassurance') &&
            $this->createConfig() &&
            $this->generateAndLinkCMSPages() &&
            $this->removeCMSPagesIfNeeded() &&
            $this->setLegalContentToOrderMails() &&
            $this->hideWirePaymentInviteAtOrderConfirmation();

        $this->emptyTemplatesCache();

        return (bool) $return;
    }

    public function hideWirePaymentInviteAtOrderConfirmation()
    {
        return $this->updateWirePaymentInviteDisplayAtOrderConfirmation(false);
    }

    public function updateWirePaymentInviteDisplayAtOrderConfirmation($display)
    {
        $wirePaymentModule = Module::getInstanceByName('ps_wirepayment');
        if (defined(get_class($wirePaymentModule) . '::FLAG_DISPLAY_PAYMENT_INVITE')) {
            $flagName = constant(get_class($wirePaymentModule) . '::FLAG_DISPLAY_PAYMENT_INVITE');
            Configuration::updateValue($flagName, $display);

            return true;
        }

        return false;
    }

    public function showWirePaymentInviteAtOrderConfirmation()
    {
        return $this->updateWirePaymentInviteDisplayAtOrderConfirmation(true);
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->dropConfig() &&
            $this->showWirePaymentInviteAtOrderConfirmation() &&
            $this->unloadTables();
    }

    public function disable($force_all = false)
    {
        $is_adv_api_disabled = (bool) Configuration::updateValue('PS_ATCP_SHIPWRAP', false);

        return parent::disable() && $is_adv_api_disabled;
    }

    public function registerModulesBackwardCompatHook()
    {
        $return = true;
        $module_to_check = array(
            'bankwire', 'cheque', 'paypal',
            'adyen', 'hipay', 'cashondelivery', 'sofortbanking',
            'pigmbhpaymill', 'ogone', 'moneybookers',
            'syspay',
        );
        $display_payment_eu_hook_id = (int) Hook::getIdByName('displayPaymentEu');
        $already_hooked_modules_ids = array_keys(Hook::getModulesFromHook($display_payment_eu_hook_id));

        foreach ($module_to_check as $module_name) {
            if (($module = Module::getInstanceByName($module_name)) !== false &&
                Module::isInstalled($module_name) &&
                $module->active &&
                !in_array($module->id, $already_hooked_modules_ids) &&
                !$module->isRegisteredInHook('displayPaymentEu')) {
                $return &= $module->registerHook('displayPaymentEu');
            }
        }

        return $return;
    }

    public function installHooks()
    {
        $hooks = array(
            'displayPaymentEu' => array(
                'name' => 'Display EU payment options (helper)',
                'description' => 'Hook to display payment options',
            ),
        );

        $return = true;

        foreach ($hooks as $hook_name => $hook) {
            if (Hook::getIdByName($hook_name)) {
                continue;
            }

            $new_hook = new Hook();
            $new_hook->name = $hook_name;
            $new_hook->title = $hook_name;
            $new_hook->description = $hook['description'];
            $new_hook->position = true;
            $new_hook->live_edit = false;

            if (!$new_hook->add()) {
                $return &= false;
                $this->_errors[] = $this->l('Could not install new hook', 'ps_legalcompliance').': '.$hook_name;
            }
        }

        return $return;
    }

    public function createConfig()
    {
        $delivery_time_available_values = array();
        $delivery_time_oos_values = array();
        $custom_cart_text_values = array();

        $langs_repository = $this->entity_manager->getRepository('Language');
        $langs = $langs_repository->findAll();

        foreach ($langs as $lang) {
            $delivery_time_available_values[(int) $lang->id] = $this->l('Delivery: 1 to 3 weeks', 'ps_legalcompliance');
            $delivery_time_oos_values[(int) $lang->id] = $this->l('Delivery: 3 to 6 weeks', 'ps_legalcompliance');
            $custom_cart_text_values[(int) $lang->id] = $this->l('The order will only be confirmed when you click on the button \'Order with an obligation to pay\' at the end of the checkout!', 'ps_legalcompliance');
        }

        /* Base settings */
        $this->processAeucFeatReorder(true);
        $this->processAeucLabelRevocationTOS(false);
        $this->processAeucLabelRevocationVP(false);
        $this->processAeucLabelSpecificPrice(true);
        $this->processAeucLabelUnitPrice(true);
        $this->processAeucLabelTaxIncExc(true);
        $this->processAeucLabelShippingIncExc(false);
        $this->processAeucLabelCombinationFrom(true);

        return Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', $delivery_time_available_values) &&
               Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_OOS', $delivery_time_oos_values) &&
               Configuration::updateValue('AEUC_LABEL_CUSTOM_CART_TEXT', $custom_cart_text_values) &&
               Configuration::updateValue('AEUC_LABEL_DELIVERY_ADDITIONAL', false) &&
               Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', false) &&
               Configuration::updateValue('AEUC_LABEL_UNIT_PRICE', true) &&
               Configuration::updateValue('AEUC_LABEL_TAX_INC_EXC', true) &&
               Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', false) &&
               Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', true) &&
               Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', false) &&
               Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', true) &&
               Configuration::updateValue('PS_TAX_DISPLAY', true) &&
               Configuration::updateValue('PS_FINAL_SUMMARY_ENABLED', true);
    }

    public function generateAndLinkCMSPages()
    {
        $cms_pages = array(
            self::LEGAL_NOTICE => array('meta_title' => $this->l('Legal notice', 'ps_legalcompliance'),
                                        'link_rewrite' => 'legal-notice',
                                        'content' => $this->l('Please add your legal information to this site.', 'ps_legalcompliance'), ),
            self::LEGAL_CONDITIONS => array('meta_title' => $this->l('Terms of Service (ToS)', 'ps_legalcompliance'),
                                            'link_rewrite' => 'terms-of-service-tos',
                                            'content' => $this->l('Please add your Terms of Service (ToS) to this site.', 'ps_legalcompliance'), ),
            self::LEGAL_REVOCATION => array('meta_title' => $this->l('Revocation terms', 'ps_legalcompliance'),
                                            'link_rewrite' => 'revocation-terms',
                                            'content' => $this->l('Please add your Revocation terms to this site.', 'ps_legalcompliance'), ),
            self::LEGAL_PRIVACY => array('meta_title' => $this->l('Privacy', 'ps_legalcompliance'),
                                        'link_rewrite' => 'privacy',
                                        'content' => $this->l('Please insert here your content about privacy. If you have activated Social Media modules, please provide a notice about third-party access to data.',
                                            'ps_legalcompliance'), ),
            self::LEGAL_SHIP_PAY => array('meta_title' => $this->l('Shipping and payment', 'ps_legalcompliance'),
                                          'link_rewrite' => 'shipping-and-payment',
                                          'content' => $this->l('Please add your Shipping and payment information to this site.', 'ps_legalcompliance'), ),
            self::LEGAL_ENVIRONMENTAL => array('meta_title' => $this->l('Environmental notice', 'ps_legalcompliance'),
                                               'link_rewrite' => 'environmental-notice',
                                               'content' => $this->l('Please add your Environmental information to this site.', 'ps_legalcompliance'), ),
        );

        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');

        $langs_repository = $this->entity_manager->getRepository('Language');
        $langs = $langs_repository->findAll();

        foreach ($cms_pages as $cms_page_role => $cms_page) {
            $cms_role = $cms_role_repository->findOneByName($cms_page_role);
            if ((int) $cms_role->id_cms == 0) {
                $cms = new CMS();
                $cms->id_cms_category = 1;
                foreach ($langs as $lang) {
                    $cms->meta_title[(int) $lang->id] = $cms_page['meta_title'];
                    $cms->link_rewrite[(int) $lang->id] = 'aeu-legal-'.$cms_page['link_rewrite'];
                    $cms->content[(int) $lang->id] = $cms_page['content'];
                }
                $cms->active = 1;
                $cms->add();
                $cms_role->id_cms = (int) $cms->id;
                $cms_role->update();
            }
        }

        return true;
    }

    public function removeCMSPagesIfNeeded()
    {
        if (Module::isInstalled('ps_linklist')) {
            $cms_repository = $this->entity_manager->getRepository('CMS');
            $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
            $cms_page_conditions_associated = $cms_role_repository->findOneByName(self::LEGAL_CONDITIONS);

            $sql = 'SELECT id_link_block, content
    				FROM '._DB_PREFIX_.'link_block';
            $link_blocks = Db::getInstance()->executeS($sql);
            foreach ($link_blocks as $link_block) {
                $conditions_found = false;
                $content = json_decode($link_block['content'], true);
                if (isset($content['cms']) && is_array($content['cms'])) {
                    foreach ($content['cms'] as $cms_key => $cms_id) {
                        if ((int) $cms_id == (int) $cms_page_conditions_associated->id_cms) {
                            unset($content['cms'][$cms_key]);
                            $conditions_found = true;
                        }
                    }
                }
                if ($conditions_found) {
                    $content['cms'] = array_values($content['cms']);
                    $content = json_encode($content);
                    Db::getInstance()->update('link_block', array('content' => $content), '`id_link_block` = '.(int) $link_block['id_link_block']);
                }
            }
        }

        return true;
    }

    public function setLegalContentToOrderMails()
    {
        $cms_roles_aeuc = $this->getCMSRoles();
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_roles_associated = $cms_role_repository->getCMSRolesAssociated();
        $role_ids_to_set = array();
        $role_id_legal_notice = false;
        $email_ids_to_set = array();
        $account_email_ids_to_set = array();
        $legal_options = array();
        $cleaned_mails_names = array();

        foreach ($cms_roles_associated as $role) {
            if ($role->name == self::LEGAL_CONDITIONS || $role->name == self::LEGAL_REVOCATION || $role->name == self::LEGAL_NOTICE) {
                $role_ids_to_set[] = $role->id;
            }
            if ($role->name == self::LEGAL_NOTICE) {
                $role_id_legal_notice = $role->id;
            }
        }

        $email_filenames = array(
            'backoffice_order',
            'credit_slip',
            'order_canceled',
            'order_changed',
            'order_conf',
            'order_customer_comment',
            'order_merchant_comment',
            'order_return_state',
            'payment',
            'refund',
        );
        foreach (AeucEmailEntity::getAll() as $email) {
            if (in_array($email['filename'], $email_filenames)) {
                $email_ids_to_set[] = $email['id_mail'];
            }
        }

        $account_newsletter_mail_filenames = array(
            'account',
            'newsletter',
            'password',
            'password_query',
        );
        foreach (AeucEmailEntity::getAll() as $email) {
            if (in_array($email['filename'], $account_newsletter_mail_filenames)) {
                $account_email_ids_to_set[] = $email['id_mail'];
            }
        }

        AeucCMSRoleEmailEntity::truncate();

        foreach ($role_ids_to_set as $role_id) {
            foreach ($email_ids_to_set as $email_id) {
                $assoc_obj = new AeucCMSRoleEmailEntity();
                $assoc_obj->id_mail = (int) $email_id;
                $assoc_obj->id_cms_role = (int) $role_id;
                $assoc_obj->save();
            }
        }

        if ($role_id_legal_notice) {
            foreach ($account_email_ids_to_set as $email_id) {
                $assoc_obj = new AeucCMSRoleEmailEntity();
                $assoc_obj->id_mail = (int)$email_id;
                $assoc_obj->id_cms_role = (int)$role_id_legal_notice;
                $assoc_obj->save();
            }
        }
        return true;
    }

    public function unloadTables()
    {
        $state = true;
        $sql = require dirname(__FILE__).'/install/sql_install.php';
        foreach ($sql as $name => $v) {
            $state &= Db::getInstance()->execute('DROP TABLE IF EXISTS '.$name);
        }

        return $state;
    }

    public function loadTables()
    {
        $state = true;

        // Create module's table
        $sql = require dirname(__FILE__).'/install/sql_install.php';
        foreach ($sql as $s) {
            $state &= Db::getInstance()->execute($s);
        }

        // Fillin CMS ROLE
        $roles_array = $this->getCMSRoles();
        $roles = array_keys($roles_array);
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');

        foreach ($roles as $role) {
            if (!$cms_role_repository->findOneByName($role)) {
                $cms_role = $cms_role_repository->getNewEntity();
                $cms_role->id_cms = 0; // No assoc at this time
                $cms_role->name = $role;
                $state &= (bool) $cms_role->save();
            }
        }

        $default_path_email = _PS_MAIL_DIR_.'en'.DIRECTORY_SEPARATOR;
        // Fill-in aeuc_mail table
        foreach ($this->emails->getAvailableMails($default_path_email) as $mail) {
            $new_email = new AeucEmailEntity();
            $new_email->filename = (string) $mail;
            $new_email->display_name = $this->emails->getCleanedMailName($mail);
            $new_email->save();
            unset($new_email);
        }

        return $state;
    }

    public function dropConfig()
    {
        return Configuration::deleteByName('AEUC_LABEL_DELIVERY_TIME_AVAILABLE') &&
               Configuration::deleteByName('AEUC_LABEL_DELIVERY_TIME_OOS') &&
               Configuration::deleteByName('AEUC_LABEL_DELIVERY_ADDITIONAL') &&
               Configuration::deleteByName('AEUC_LABEL_SPECIFIC_PRICE') &&
               Configuration::deleteByName('AEUC_LABEL_UNIT_PRICE') &&
               Configuration::deleteByName('AEUC_LABEL_TAX_INC_EXC') &&
               Configuration::deleteByName('AEUC_LABEL_REVOCATION_TOS') &&
               Configuration::deleteByName('AEUC_LABEL_REVOCATION_VP') &&
               Configuration::deleteByName('AEUC_LABEL_SHIPPING_INC_EXC') &&
               Configuration::deleteByName('AEUC_LABEL_COMBINATION_FROM') &&
               Configuration::deleteByName('AEUC_LABEL_CUSTOM_CART_TEXT') &&
               Configuration::updateValue('PS_ATCP_SHIPWRAP', false);
    }

    /*
        This method checks if cart has virtual products
        It's better to add this method (as hasVirtualProduct) and add 'protected static $_hasVirtualProduct = array(); property
        in Cart class in next version of prestashop.
    */
    private function hasCartVirtualProduct(Cart $cart)
    {
        $products = $cart->getProducts();

        if (!count($products)) {
            return false;
        }

        foreach ($products as $product) {
            if ($product['is_virtual']) {
                return true;
            }
        }

        return false;
    }

    public function hookDisplayCartTotalPriceLabel($param)
    {
        $smartyVars = array();
        if ((bool) Configuration::get('AEUC_LABEL_TAX_INC_EXC') === true) {
            $customer_default_group_id = (int) $this->context->customer->id_default_group;
            $customer_default_group = new Group($customer_default_group_id);

            if ((bool) Configuration::get('PS_TAX') === true && $this->context->country->display_tax_label &&
                !(Validate::isLoadedObject($customer_default_group) && (bool) $customer_default_group->price_display_method === true)) {
                $smartyVars['price']['tax_str_i18n'] = $this->l('Tax included', 'ps_legalcompliance');
            } else {
                $smartyVars['price']['tax_str_i18n'] = $this->l('Tax excluded', 'ps_legalcompliance');
            }
        }

        if (isset($param['from'])) {
            if ($param['from'] == 'shopping_cart') {
                $smartyVars['css_class'] = 'aeuc_tax_label_shopping_cart';
            }
            if ($param['from'] == 'blockcart') {
                $smartyVars['css_class'] = 'aeuc_tax_label_blockcart';
            }
        }

        $this->context->smarty->assign(array('smartyVars' => $smartyVars));

        return $this->display(__FILE__, 'displayCartTotalPriceLabel.tpl');
    }

    public function hookDisplayOverrideTemplate($param)
    {
        if (isset($this->context->controller->php_self) && ($this->context->controller->php_self == 'order')) {
            return $this->getTemplatePath('hookDisplayOverrideTemplateFooter.tpl');
        }
    }

    public function hookDisplayCheckoutSummaryTop($param)
    {
        $cart_url = $this->context->link->getPageLink(
            'cart',
            null,
            $this->context->language->id,
            ['action' => 'show']
            );
        $this->context->smarty->assign('link_shopping_cart', $cart_url);

        return $this->display(__FILE__, 'hookDisplayCheckoutSummaryTop.tpl');
    }

    public function hookDisplayReassurance($param)
    {
        if (isset($this->context->controller->php_self) && (in_array($this->context->controller->php_self, array('order', 'cart')))) {
            $custom_cart_text = Configuration::get('AEUC_LABEL_CUSTOM_CART_TEXT', (int) $this->context->language->id);
            if (trim($custom_cart_text) == '') {
                return false;
            } else {
                $this->context->smarty->assign('custom_cart_text', $custom_cart_text);
                return $this->display(__FILE__, 'hookDisplayReassurance.tpl');
            }
        }
    }

    public function hookDisplayFooter($param)
    {
        $cms_roles_to_be_displayed = array(self::LEGAL_NOTICE,
            self::LEGAL_CONDITIONS,
            self::LEGAL_REVOCATION,
            self::LEGAL_PRIVACY,
            self::LEGAL_SHIP_PAY,
            self::LEGAL_ENVIRONMENTAL, );

        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_pages_associated = $cms_role_repository->findByName($cms_roles_to_be_displayed);
        $is_ssl_enabled = (bool) Configuration::get('PS_SSL_ENABLED');
        $cms_links = array();
        foreach ($cms_pages_associated as $cms_page_associated) {
            if ($cms_page_associated instanceof CMSRole && (int) $cms_page_associated->id_cms > 0) {
                $cms = new CMS((int) $cms_page_associated->id_cms);
                $cms_links[] = array('link' => $this->context->link->getCMSLink($cms->id, null, $is_ssl_enabled),
                                     'id' => 'cms-page-'.$cms->id,
                                     'title' => $cms->meta_title[$this->context->language->id],
                                     'desc' => $cms->meta_description[$this->context->language->id],
                );
            }
        }
        $this->context->smarty->assign('cms_links', $cms_links);

        return $this->display(__FILE__, 'hookDisplayFooter.tpl');
    }

    public function hookDisplayFooterAfter($param)
    {
        if (isset($this->context->controller->php_self)) {
            if (in_array($this->context->controller->php_self, array('index', 'category', 'prices-drop', 'new-products', 'best-sales', 'search', 'product'))) {
                $cms_repository = $this->entity_manager->getRepository('CMS');
                $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
                $cms_page_shipping_pay = $cms_role_repository->findOneByName(self::LEGAL_SHIP_PAY);

                $link_shipping = false;
                if ((int) $cms_page_shipping_pay->id_cms > 0) {
                    $cms_shipping_pay = $cms_repository->i10nFindOneById((int) $cms_page_shipping_pay->id_cms,
                        (int) $this->context->language->id,
                        (int) $this->context->shop->id);
                    $link_shipping =
                        $this->context->link->getCMSLink($cms_shipping_pay, $cms_shipping_pay->link_rewrite, (bool) Configuration::get('PS_SSL_ENABLED'));
                }

                if ($this->context->controller->php_self == 'product') {
                    $delivery_addtional_info = Configuration::get('AEUC_LABEL_DELIVERY_ADDITIONAL', (int) $this->context->language->id);
                    if (trim($delivery_addtional_info) == '') {
                        return false;
                    }
                    $this->context->smarty->assign('link_shipping', $link_shipping);
                    $this->context->smarty->assign('delivery_additional_information', $delivery_addtional_info);
                } else {
                    $customer_default_group_id = (int) $this->context->customer->id_default_group;
                    $customer_default_group = new Group($customer_default_group_id);

                    if ((bool) Configuration::get('PS_TAX') === true && $this->context->country->display_tax_label &&
                        !(Validate::isLoadedObject($customer_default_group) && (bool) $customer_default_group->price_display_method === true)) {
                        $tax_included = true;
                    } else {
                        $tax_included = false;
                    }

                    $this->context->smarty->assign('show_shipping', (bool) Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC') === true);
                    $this->context->smarty->assign('link_shipping', $link_shipping);
                    $this->context->smarty->assign('tax_included', $tax_included);
                }

                return $this->display(__FILE__, 'hookDisplayFooterAfter.tpl');
            }
        }
    }

    /* This hook is present to maintain backward compatibility */
    public function hookAdvancedPaymentOptions($param)
    {
        $legacyOptions = Hook::exec('displayPaymentEU', array(), null, true);
        $newOptions = array();

        Media::addJsDef(array('aeuc_tos_err_str' => Tools::htmlentitiesUTF8($this->l('You must agree to our Terms of Service before going any further!',
                                                                                     'ps_legalcompliance'))));
        Media::addJsDef(array('aeuc_submit_err_str' => Tools::htmlentitiesUTF8($this->l('Something went wrong. If the problem persists, please contact us.',
                                                                                        'ps_legalcompliance'))));
        Media::addJsDef(array('aeuc_no_pay_err_str' => Tools::htmlentitiesUTF8($this->l('Select a payment option first.',
                                                                                        'ps_legalcompliance'))));
        Media::addJsDef(array('aeuc_virt_prod_err_str' => Tools::htmlentitiesUTF8($this->l('Please check the "Revocation of virtual products" box first!',
                                                                                           'ps_legalcompliance'))));
        if ($legacyOptions) {
            foreach ($legacyOptions as $module_name => $legacyOption) {
                if (!$legacyOption) {
                    continue;
                }

                foreach (PaymentOption::convertLegacyOption($legacyOption) as $option) {
                    $option->setModuleName($module_name);
                    $to_be_cleaned = $option->getForm();
                    if ($to_be_cleaned) {
                        $cleaned = str_replace('@hiddenSubmit', '', $to_be_cleaned);
                        $option->setForm($cleaned);
                    }
                    $newOptions[] = $option;
                }
            }
        }

        return $newOptions;
    }

    public function hookActionEmailAddAfterContent($param)
    {
        if (!isset($param['template']) || !isset($param['template_html']) || !isset($param['template_txt'])) {
            return;
        }

        $tpl_name = (string) $param['template'];
        $tpl_name_exploded = explode('.', $tpl_name);
        if (is_array($tpl_name_exploded)) {
            $tpl_name = (string) $tpl_name_exploded[0];
        }

        $id_lang = (int) $param['id_lang'];
        $mail_id = AeucEmailEntity::getMailIdFromTplFilename($tpl_name);
        if (!isset($mail_id['id_mail'])) {
            return;
        }

        $mail_id = (int) $mail_id['id_mail'];
        $cms_role_ids = AeucCMSRoleEmailEntity::getCMSRoleIdsFromIdMail($mail_id);
        if (!$cms_role_ids) {
            return;
        }

        $tmp_cms_role_list = array();
        foreach ($cms_role_ids as $cms_role_id) {
            $tmp_cms_role_list[] = $cms_role_id['id_cms_role'];
        }

        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_roles = $cms_role_repository->findByIdCmsRole($tmp_cms_role_list);
        if (!$cms_roles) {
            return;
        }

        $cms_repo = $this->entity_manager->getRepository('CMS');
        $cms_contents = array();

        foreach ($cms_roles as $cms_role) {
            $cms_page = $cms_repo->i10nFindOneById((int) $cms_role->id_cms, $id_lang, $this->context->shop->id);

            if (!isset($cms_page->content)) {
                continue;
            }

            $cms_contents[] = $cms_page->content;
            $param['template_txt'] .= strip_tags($cms_page->content, true);
        }

        $this->context->smarty->assign(array('cms_contents' => $cms_contents));
        $param['template_html'] .= $this->display(__FILE__, 'hook-email-wrapper.tpl');
    }

    public function hookSendMailAlterTemplateVars($param)
    {
        if (!isset($param['template']) && !isset($param['{carrier}'])) {
            return;
        }

        $tpl_name = (string) $param['template'];
        $tpl_name_exploded = explode('.', $tpl_name);
        if (is_array($tpl_name_exploded)) {
            $tpl_name = (string) $tpl_name_exploded[0];
        }

        if ('order_conf' !== $tpl_name) {
            return;
        }

        $carrier = new Carrier((int) $param['cart']->id_carrier);

        $param['template_vars']['{carrier}'] .= ' - '.$carrier->delay[(int) $param['cart']->id_lang];
    }

    public function hookHeader($param)
    {
        $this->context->controller->registerStylesheet('modules-aeuc_front', 'modules/'.$this->name.'/views/css/aeuc_front.css', ['media' => 'all', 'priority' => 150]);

        if (isset($this->context->controller->php_self) && ($this->context->controller->php_self == 'cms')) {
            if ($this->isPrintableCMSPage()) {
                $this->context->controller->registerStylesheet('modules-aeuc_print', 'modules/'.$this->name.'/views/css/aeuc_print.css', ['media' => 'print', 'priority' => 150]);
            }
        }
        if (Tools::getValue('direct_print') == '1') {
            $this->context->controller->registerJavascript('modules-fo_aeuc_print', 'modules/'.$this->name.'/views/js/fo_aeuc_print.js', ['position' => 'bottom', 'priority' => 150]);
        }
    }

    protected function isPrintableCMSPage()
    {
        $printable_cms_pages = array();
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        foreach (array(self::LEGAL_CONDITIONS, self::LEGAL_REVOCATION, self::LEGAL_SHIP_PAY, self::LEGAL_PRIVACY) as $cms_page_name) {
            $cms_page_associated = $cms_role_repository->findOneByName($cms_page_name);
            if ($cms_page_associated instanceof CMSRole && (int) $cms_page_associated->id_cms > 0) {
                $printable_cms_pages[] = (int) $cms_page_associated->id_cms;
            }
        }

        return in_array(Tools::getValue('id_cms'), $printable_cms_pages);
    }

    public function hookDisplayCMSDisputeInformation($params)
    {
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_page_associated = $cms_role_repository->findOneByName(self::LEGAL_NOTICE);
        if ($cms_page_associated instanceof CMSRole && (int) $cms_page_associated->id_cms > 0) {
            if (Tools::getValue('id_cms') == $cms_page_associated->id_cms) {
                return $this->display(__FILE__, 'hookDisplayCMSDisputeInformation.tpl');
            }
        }
    }

    public function hookTermsAndConditions($param)
    {
        $returned_terms_and_conditions = array();

        $cms_repository = $this->entity_manager->getRepository('CMS');
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_page_conditions_associated = $cms_role_repository->findOneByName(self::LEGAL_CONDITIONS);
        $cms_page_revocation_associated = $cms_role_repository->findOneByName(self::LEGAL_REVOCATION);

        if ((int) $cms_page_conditions_associated->id_cms > 0 && (int) $cms_page_revocation_associated->id_cms > 0) {
            $cms_conditions = $cms_repository->i10nFindOneById((int) $cms_page_conditions_associated->id_cms,
                                                               (int) $this->context->language->id,
                                                               (int) $this->context->shop->id);
            $link_conditions =
                $this->context->link->getCMSLink($cms_conditions, $cms_conditions->link_rewrite, (bool) Configuration::get('PS_SSL_ENABLED'));

            $cms_revocation = $cms_repository->i10nFindOneById((int) $cms_page_revocation_associated->id_cms,
                                                               (int) $this->context->language->id,
                                                               (int) $this->context->shop->id);
            $link_revocation =
                $this->context->link->getCMSLink($cms_revocation, $cms_revocation->link_rewrite, (bool) Configuration::get('PS_SSL_ENABLED'));

            $termsAndConditions = new TermsAndConditions();
            $termsAndConditions
                ->setText(
                    $this->l('I agree to the [terms of service] and [revocation terms] and will adhere to them unconditionally.', [], 'Checkout'),
                    $link_conditions,
                    $link_revocation
                )
                ->setIdentifier('terms-and-conditions')
            ;
            $returned_terms_and_conditions[] = $termsAndConditions;
        }

        if ((bool) Configuration::get('AEUC_LABEL_REVOCATION_VP') && $this->hasCartVirtualProduct($this->context->cart)) {
            $termsAndConditions = new TermsAndConditions();

            $termsAndConditions
                ->setText(
                    $this->trans(
                        '[1]For digital goods:[/1] I want immediate access to the digital content and I acknowledge that thereby I lose my right to cancel once the service has begun.[2][1]For services:[/1] I agree to the starting of the service and I acknowledge that I lose my right to cancel once the service has been fully performed.',
                        array(
                            '[1]' => '<strong>',
                            '[/1]' => '</strong>',
                            '[2]' => '<br>',
                        ),
                        'Modules.LegalCompliance.Shop'
                    )
                )
                ->setIdentifier('virtual-products')
            ;

            $returned_terms_and_conditions[] = $termsAndConditions;
        }

        if (sizeof($returned_terms_and_conditions) > 0) {
            return $returned_terms_and_conditions;
        } else {
            return false;
        }
    }

    public function hookDisplayCMSPrintButton($param)
    {
        if ($this->isPrintableCMSPage()) {
            $this->context->smarty->assign('directPrint', Tools::getValue('content_only') != '1');

            $cms_repository = $this->entity_manager->getRepository('CMS');
            $cms_current = $cms_repository->i10nFindOneById((int)Tools::getValue('id_cms'),
                                                            (int)$this->context->language->id,
                                                            (int)$this->context->shop->id);
            $cms_current_link =
            $this->context->link->getCMSLink($cms_current, $cms_current->link_rewrite, (bool)Configuration::get('PS_SSL_ENABLED'));

            if (!strpos($cms_current_link, '?')) {
                $cms_current_link .= '?direct_print=1';
            } else {
                $cms_current_link .= '&direct_print=1';
            }

            $this->context->smarty->assign('print_link', $cms_current_link);
            return $this->display(__FILE__, 'hookDisplayCMSPrintButton.tpl');
        }
    }

    public function hookDisplayProductPriceBlock($param)
    {
        if (!isset($param['product']) || !isset($param['type'])) {
            return;
        }

        $product = $param['product'];
        $hook_type = $param['type'];

        if (is_array($product)) {
            $product_repository = $this->entity_manager->getRepository('Product');
            $product = $product_repository->findOne((int) $product['id_product']);
        }
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $smartyVars = array();

        /* Handle Product Combinations label */
        if ($param['type'] == 'before_price' && (bool) Configuration::get('AEUC_LABEL_COMBINATION_FROM') === true) {
            if ($product->hasAttributes()) {
                $need_display = false;
                $combinations = $product->getAttributeCombinations($this->context->language->id);
                if ($combinations && is_array($combinations)) {
                    foreach ($combinations as $combination) {
                        if ((float) $combination['price'] != 0) {
                            $need_display = true;
                            break;
                        }
                    }

                    unset($combinations);

                    if ($need_display) {
                        $smartyVars['before_price'] = array();
                        $smartyVars['before_price']['from_str_i18n'] = $this->l('From', 'ps_legalcompliance');

                        return $this->dumpHookDisplayProductPriceBlock($smartyVars, $hook_type);
                    }
                }

                return;
            }
        }

        /* Handle Specific Price label*/
        if ($param['type'] == 'old_price' && (bool) Configuration::get('AEUC_LABEL_SPECIFIC_PRICE') === true && 'catalog/_partials/miniatures/product.tpl' != $param['smarty']->template_resource) {
            $smartyVars['old_price'] = array();
            $smartyVars['old_price']['before_str_i18n'] = $this->l('Our previous price', 'ps_legalcompliance');

            return $this->dumpHookDisplayProductPriceBlock($smartyVars, $hook_type);
        }

        /* Handle Shipping Inc./Exc.*/
        if ($param['type'] == 'price') {
            $smartyVars['price'] = array();
            $need_shipping_label = true;

            if ((bool) Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC') === true && $need_shipping_label === true) {
                if (!$product->is_virtual) {
                    $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
                    $cms_repository = $this->entity_manager->getRepository('CMS');
                    $cms_page_associated = $cms_role_repository->findOneByName(self::LEGAL_SHIP_PAY);

                    if (isset($cms_page_associated->id_cms) && $cms_page_associated->id_cms != 0) {
                        $cms_ship_pay_id = (int) $cms_page_associated->id_cms;
                        $cms_revocations = $cms_repository->i10nFindOneById($cms_ship_pay_id, $this->context->language->id,
                                                                            $this->context->shop->id);
                        $is_ssl_enabled = (bool) Configuration::get('PS_SSL_ENABLED');
                        $link_ship_pay = $this->context->link->getCMSLink($cms_revocations, $cms_revocations->link_rewrite, $is_ssl_enabled);

                        $smartyVars['ship'] = array();
                        $smartyVars['ship']['link_ship_pay'] = $link_ship_pay;
                        $smartyVars['ship']['ship_str_i18n'] = $this->l('Shipping excluded', 'ps_legalcompliance');
                    }
                }
            }

            return $this->dumpHookDisplayProductPriceBlock($smartyVars, $hook_type);
        }

        /* Handle Delivery time label */
        if ($param['type'] == 'after_price' && !$product->is_virtual) {
            $context_id_lang = $this->context->language->id;
            $is_product_available = (StockAvailable::getQuantityAvailableByProduct($product->id) >= 1 ? true : false);
            $smartyVars['after_price'] = array();

            if ($is_product_available) {
                $contextualized_content =
                    Configuration::get('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', (int) $context_id_lang);
                $smartyVars['after_price']['delivery_str_i18n'] = $contextualized_content;
            } else {
                $contextualized_content = Configuration::get('AEUC_LABEL_DELIVERY_TIME_OOS', (int) $context_id_lang);
                $smartyVars['after_price']['delivery_str_i18n'] = $contextualized_content;
            }
            $delivery_addtional_info = Configuration::get('AEUC_LABEL_DELIVERY_ADDITIONAL', (int) $context_id_lang);
            if (trim($delivery_addtional_info) != '') {
                $smartyVars['after_price']['delivery_str_i18n'] .= '*';
            }

            return $this->dumpHookDisplayProductPriceBlock($smartyVars, $hook_type);
        }

        /* Handle Taxes Inc./Exc.*/
        if ($param['type'] == 'list_taxes') {
            $smartyVars['list_taxes'] = array();
            if ((bool) Configuration::get('AEUC_LABEL_TAX_INC_EXC') === true) {
                $customer_default_group_id = (int) $this->context->customer->id_default_group;
                $customer_default_group = new Group($customer_default_group_id);

                if ((bool) Configuration::get('PS_TAX') === true && $this->context->country->display_tax_label &&
                    !(Validate::isLoadedObject($customer_default_group) && (bool) $customer_default_group->price_display_method === true)) {
                    $smartyVars['list_taxes']['tax_str_i18n'] = $this->l('Tax included', 'ps_legalcompliance');
                } else {
                    $smartyVars['list_taxes']['tax_str_i18n'] = $this->l('Tax excluded', 'ps_legalcompliance');
                }
            }

            return $this->dumpHookDisplayProductPriceBlock($smartyVars, $hook_type);
        }

        /* Handle Unit prices */
        if ($param['type'] == 'unit_price') {
            if ((!empty($product->unity) && $product->unit_price_ratio > 0.000000)) {
                $smartyVars['unit_price'] = array();
                if ((bool) Configuration::get('AEUC_LABEL_UNIT_PRICE') === true) {
                    if (!(isset($this->context->controller->php_self) && ($this->context->controller->php_self == 'product'))) {
                        $priceDisplay = Product::getTaxCalculationMethod((int) $this->context->cookie->id_customer);
                        if (!$priceDisplay || $priceDisplay == 2) {
                            $productPrice = $product->getPrice(true, null, 6);
                        } else {
                            $productPrice = $product->getPrice(false, null, 6);
                        }
                        $smartyVars['unit_price']['unit_price'] = $param['product']['unit_price_full'];
                        $smartyVars['unit_price']['unity'] = $product->unity;
                    }
                }

                return $this->dumpHookDisplayProductPriceBlock($smartyVars, $hook_type, $product->id);
            }
        }
    }

    public function hookDisplayCheckoutSubtotalDetails($param)
    {
        // Display "under conditions" when the shipping subtotal equals 0
        if ('shipping' === $param['subtotal']['type'] && 0 === $param['subtotal']['amount']) {
            $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
            $cms_page_shipping_and_payment = $cms_role_repository->findOneByName(self::LEGAL_SHIP_PAY);
            $link = $this->context->link->getCMSLink((int)$cms_page_shipping_and_payment->id_cms);

            $this->context->smarty->assign(array('link' => $link));
            return $this->display(__FILE__, 'hookDisplayCartPriceBlock_shipping_details.tpl');
        }
    }

    private function emptyTemplatesCache()
    {
        $this->_clearCache('product.tpl');
        $this->_clearCache('product-list.tpl');
    }

    private function dumpHookDisplayProductPriceBlock(array $smartyVars, $hook_type, $additional_cache_param = false)
    {
        $cache_id = sha1($hook_type.$additional_cache_param);
        $this->context->smarty->assign(array('smartyVars' => $smartyVars));
        $this->context->controller->addJS($this->_path.'views/js/fo_aeuc_tnc.js', true);
        $template = 'hookDisplayProductPriceBlock_'.$hook_type.'.tpl';

        return $this->display(__FILE__, $template, $cache_id);
    }

    /**
     * Load the configuration form.
     */
    public function getContent()
    {
        $theme_warning = null;
        $success_band = $this->_postProcess();

        $infoMsg = $this->trans(
            'This module helps European merchants to comply with legal requirements. Learn how to configure the module and other shop parameters so that you\'re in compliance with the law.[1][2]PrestaShop 1.7 legal compliance documentation[/2]',
            array(
                '[1]' => '<br>',
                '[2]' => '<a href="http://doc.prestashop.com/display/PS17/Complying+with+the+European+legislation" target="_blank">',
                '[/2]' => '</a>',
            ),
            'Modules.LegalCompliance.Admin'
        );

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('errors', $this->_errors);
        $this->context->controller->addCSS($this->_path.'views/css/configure.css', 'all');
        // Render all required form for each 'part'
        $formLabelsManager = $this->renderFormLabelsManager();
        $formFeaturesManager = $this->renderFormFeaturesManager();
        $formLegalContentManager = $this->renderFormLegalContentManager();
        $formEmailAttachmentsManager = $this->renderFormEmailAttachmentsManager();

        return $theme_warning.$this->adminDisplayInformation($infoMsg).$success_band.$formLabelsManager.$formFeaturesManager.$formLegalContentManager.
               $formEmailAttachmentsManager;
    }

    /**
     * Save form data.
     */
    protected function _postProcess()
    {
        $has_processed_something = false;

        $post_keys_switchable =
            array_keys(array_merge($this->getConfigFormLabelsManagerValues(), $this->getConfigFormFeaturesManagerValues()));

        $post_keys_complex = array('AEUC_legalContentManager',
                                   'AEUC_emailAttachmentsManager',
                                   'discard_tpl_warn',
        );

        $i10n_inputs_received = array();
        $received_values = Tools::getAllValues();

        foreach (array_keys($received_values) as $key_received) {
            /* Case its one of form with only switches in it */
            if (in_array($key_received, $post_keys_switchable)) {
                $is_option_active = Tools::getValue($key_received);
                $key = Tools::strtolower($key_received);
                $key = Tools::toCamelCase($key);

                if (method_exists($this, 'process'.$key)) {
                    $this->{'process'.$key}($is_option_active);
                    $has_processed_something = true;
                }
                continue;
            }
            /* Case we are on more complex forms */
            if (in_array($key_received, $post_keys_complex)) {
                // Clean key
                $key = Tools::strtolower($key_received);
                $key = Tools::toCamelCase($key, true);

                if (method_exists($this, 'process'.$key)) {
                    $this->{'process'.$key}();
                    $has_processed_something = true;
                }
            }

            /* Case Multi-lang input */
            if (strripos($key_received, 'AEUC_LABEL_DELIVERY_TIME_AVAILABLE') !== false) {
                $exploded = explode('_', $key_received);
                $count = count($exploded);
                $id_lang = (int) $exploded[$count - 1];
                $i10n_inputs_received['AEUC_LABEL_DELIVERY_TIME_AVAILABLE'][$id_lang] = $received_values[$key_received];
            }
            if (strripos($key_received, 'AEUC_LABEL_DELIVERY_TIME_OOS') !== false) {
                $exploded = explode('_', $key_received);
                $count = count($exploded);
                $id_lang = (int) $exploded[$count - 1];
                $i10n_inputs_received['AEUC_LABEL_DELIVERY_TIME_OOS'][$id_lang] = $received_values[$key_received];
            }
            if (strripos($key_received, 'AEUC_LABEL_CUSTOM_CART_TEXT') !== false) {
                $exploded = explode('_', $key_received);
                $count = count($exploded);
                $id_lang = (int) $exploded[$count - 1];
                $i10n_inputs_received['AEUC_LABEL_CUSTOM_CART_TEXT'][$id_lang] = $received_values[$key_received];
            }
            if (strripos($key_received, 'AEUC_LABEL_DELIVERY_ADDITIONAL') !== false) {
                $exploded = explode('_', $key_received);
                $count = count($exploded);
                $id_lang = (int) $exploded[$count - 1];
                $i10n_inputs_received['AEUC_LABEL_DELIVERY_ADDITIONAL'][$id_lang] = $received_values[$key_received];
            }
        }

        if (count($i10n_inputs_received) > 0) {
            $this->processAeucLabelMultiLang($i10n_inputs_received);
            $has_processed_something = true;
        }

        if ($has_processed_something) {
            $this->emptyTemplatesCache();

            return (count($this->_errors) ? $this->displayError($this->_errors) : '').
                   (count($this->_warnings) ? $this->displayWarning($this->_warnings) : '').
                   $this->displayConfirmation($this->l('Settings saved successfully!', 'ps_legalcompliance'));
        } else {
            return (count($this->_errors) ? $this->displayError($this->_errors) : '').
                   (count($this->_warnings) ? $this->displayWarning($this->_warnings) : '').'';
        }
    }

    protected function processAeucLabelMultiLang(array $i10n_inputs)
    {
        if (isset($i10n_inputs['AEUC_LABEL_DELIVERY_TIME_AVAILABLE'])) {
            Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', $i10n_inputs['AEUC_LABEL_DELIVERY_TIME_AVAILABLE']);
        }
        if (isset($i10n_inputs['AEUC_LABEL_DELIVERY_TIME_OOS'])) {
            Configuration::updateValue('AEUC_LABEL_DELIVERY_TIME_OOS', $i10n_inputs['AEUC_LABEL_DELIVERY_TIME_OOS']);
        }
        if (isset($i10n_inputs['AEUC_LABEL_DELIVERY_ADDITIONAL'])) {
            Configuration::updateValue('AEUC_LABEL_DELIVERY_ADDITIONAL', $i10n_inputs['AEUC_LABEL_DELIVERY_ADDITIONAL']);
        }
        if (isset($i10n_inputs['AEUC_LABEL_CUSTOM_CART_TEXT'])) {
            Configuration::updateValue('AEUC_LABEL_CUSTOM_CART_TEXT', $i10n_inputs['AEUC_LABEL_CUSTOM_CART_TEXT']);
        }
    }

    protected function processAeucLabelCombinationFrom($is_option_active)
    {
        if ((bool) $is_option_active) {
            Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', false);
        }
    }

    protected function processAeucLabelSpecificPrice($is_option_active)
    {
        if ((bool) $is_option_active) {
            Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', false);
        }
    }

    protected function processAeucEmailAttachmentsManager()
    {
        $json_attach_assoc = Tools::jsonDecode(Tools::getValue('emails_attach_assoc'));

        if (!$json_attach_assoc) {
            return;
        }

        // Empty previous assoc to make new ones
        AeucCMSRoleEmailEntity::truncate();

        foreach ($json_attach_assoc as $assoc) {
            $assoc_obj = new AeucCMSRoleEmailEntity();
            $assoc_obj->id_mail = $assoc->id_mail;
            $assoc_obj->id_cms_role = $assoc->id_cms_role;

            if (!$assoc_obj->save()) {
                $this->_errors[] = $this->l('Failed to associate legal content with an email template.', 'ps_legalcompliance');
            }
        }
    }

    protected function processAeucLabelRevocationTOS($is_option_active)
    {
        // Check first if LEGAL_REVOCATION CMS Role has been set before doing anything here
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_page_associated = $cms_role_repository->findOneByName(self::LEGAL_REVOCATION);
        $cms_roles = $this->getCMSRoles();

        if ((bool) $is_option_active) {
            if (!$cms_page_associated instanceof CMSRole || (int) $cms_page_associated->id_cms == 0) {
                $this->_errors[] =
                    sprintf($this->l('\'Revocation Terms within ToS\' label cannot be activated unless you associate "%s" role with a Page.',
                                     'ps_legalcompliance'), (string) $cms_roles[self::LEGAL_REVOCATION]);

                return;
            }
            Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', false);
        }
    }

    protected function processAeucLabelRevocationVP($is_option_active)
    {
        if ((bool) $is_option_active) {
            Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', false);
        }
    }

    protected function processAeucLabelShippingIncExc($is_option_active)
    {
        // Check first if LEGAL_SHIP_PAY CMS Role has been set before doing anything here
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_page_associated = $cms_role_repository->findOneByName(self::LEGAL_SHIP_PAY);
        $cms_roles = $this->getCMSRoles();

        if ((bool) $is_option_active) {
            if (!$cms_page_associated instanceof CMSRole || (int) $cms_page_associated->id_cms === 0) {
                $this->_errors[] =
                    sprintf($this->l('Shipping fees label cannot be activated unless you associate "%s" role with a Page.',
                                     'ps_legalcompliance'), (string) $cms_roles[self::LEGAL_SHIP_PAY]);

                return;
            }
            Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', false);
        }
    }

    protected function processAeucLabelTaxIncExc($is_option_active)
    {
        $countries = Country::getCountries((int) Context::getContext()->language->id, true);
        foreach ($countries as $id_country => $country_row) {
            $country = new Country($id_country);
            $country->display_tax_label = (bool) $is_option_active;
            $country->save();
        }
        Configuration::updateValue('AEUC_LABEL_TAX_INC_EXC', (bool) $is_option_active);
    }

    protected function processAeucLabelUnitPrice($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_UNIT_PRICE', $is_option_active);
    }

    protected function processPsAtcpShipWrap($is_option_active)
    {
        Configuration::updateValue('PS_ATCP_SHIPWRAP', $is_option_active);
    }

    protected function processAeucFeatReorder($is_option_active)
    {
        if ((bool) $is_option_active) {
            Configuration::updateValue('PS_DISALLOW_HISTORY_REORDERING', false);
        } else {
            Configuration::updateValue('PS_DISALLOW_HISTORY_REORDERING', true);
        }
    }

    protected function processAeucLegalContentManager()
    {
        $posted_values = Tools::getAllValues();
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');

        foreach ($posted_values as $key_name => $assoc_cms_id) {
            if (strpos($key_name, 'CMSROLE_') !== false) {
                $exploded_key_name = explode('_', $key_name);
                $cms_role = $cms_role_repository->findOne((int) $exploded_key_name[1]);
                $cms_role->id_cms = (int) $assoc_cms_id;
                $cms_role->update();
            }
        }
        unset($cms_role);
    }

    protected function getCMSRoles()
    {
        return array(self::LEGAL_NOTICE => $this->l('Legal notice', 'ps_legalcompliance'),
                     self::LEGAL_CONDITIONS => $this->l('Terms of Service (ToS)', 'ps_legalcompliance'),
                     self::LEGAL_REVOCATION => $this->l('Revocation terms', 'ps_legalcompliance'),
                     self::LEGAL_REVOCATION_FORM => $this->l('Revocation form', 'ps_legalcompliance'),
                     self::LEGAL_PRIVACY => $this->l('Privacy', 'ps_legalcompliance'),
                     self::LEGAL_ENVIRONMENTAL => $this->l('Environmental notice', 'ps_legalcompliance'),
                     self::LEGAL_SHIP_PAY => $this->l('Shipping and payment', 'ps_legalcompliance'),
        );
    }

    /**
     * Create the form that will let user choose all the wording options.
     */
    protected function renderFormLabelsManager()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAEUC_labelsManager';
        $helper->currentIndex =
            $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.
            $this->tab.'&module_name='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules');
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars =
            array('fields_value' => $this->getConfigFormLabelsManagerValues(),
                  /* Add values for your inputs */
                  'languages' => $this->context->controller->getLanguages(),
                  'id_language' => $this->context->language->id,
            );

        return $helper->generateForm(array($this->getConfigFormLabelsManager()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormLabelsManager()
    {
        return array('form' => array('legend' => array('title' => $this->l('Labels', 'ps_legalcompliance'),
                                                       'icon' => 'icon-tags',
        ),
                                     'input' => array(array('type' => 'text',
                                                             'lang' => true,
                                                             'label' => $this->l('Delivery time label (available products)', 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_DELIVERY_TIME_AVAILABLE',
                                                             'desc' => $this->l('It is displayed on the product page and in the footer of other pages. Leave the field empty to disable.', 'ps_legalcompliance'),
                                                             'hint' => $this->l('Indicate the delivery time for your in-stock products.', 'ps_legalcompliance'),
                                                       ),
                                                       array('type' => 'text',
                                                             'lang' => true,
                                                             'label' => $this->l('Delivery time label (out-of-stock products)',
                                                                                 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_DELIVERY_TIME_OOS',
                                                             'desc' => $this->l('It is displayed on the product page and in the footer of other pages. Leave the field empty to disable.', 'ps_legalcompliance'),
                                                             'hint' => $this->l('Indicate the delivery time for your out-of-stock products.', 'ps_legalcompliance'),
                                                       ),
                                                       array('type' => 'text',
                                                             'lang' => true,
                                                             'label' => $this->l('Additional information about delivery time',
                                                                                 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_DELIVERY_ADDITIONAL',
                                                             'desc' => $this->l('If you specified a delivery time, this additional information is displayed in the footer of product pages with a link to the "Shipping & Payment" Page. Leave the field empty to disable.', 'ps_legalcompliance'),
                                                             'hint' => $this->l('Indicate for which countries your delivery time applies.', 'ps_legalcompliance'),
                                                       ),
                                                       array('type' => 'switch',
                                                             'label' => $this->l(' \'Our previous price\' label', 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_SPECIFIC_PRICE',
                                                             'is_bool' => true,
                                                             'desc' => $this->l('When a product is on sale, displays a \'Our previous price\' label before the original price crossed out, next to the price on the product page.', 'ps_legalcompliance'),
                                                             'values' => array(array('id' => 'active_on',
                                                                                      'value' => true,
                                                                                      'label' => $this->l('Enabled', 'ps_legalcompliance'),
                                                                                ),
                                                                                array('id' => 'active_off',
                                                                                      'value' => false,
                                                                                      'label' => $this->l('Disabled', 'ps_legalcompliance'),
                                                                                ),
                                                             ),
                                                       ),
                                                       array('type' => 'switch',
                                                             'label' => $this->l('Tax \'inc./excl.\' label', 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_TAX_INC_EXC',
                                                             'is_bool' => true,
                                                             'desc' => $this->l('Displays whether the tax is included on the product page (\'Tax incl./excl.\' label) and adds a short mention in the footer of other pages.', 'ps_legalcompliance'),
                                                             'values' => array(array('id' => 'active_on',
                                                                                      'value' => true,
                                                                                      'label' => $this->l('Enabled', 'ps_legalcompliance'),
                                                                                ),
                                                                                array('id' => 'active_off',
                                                                                      'value' => false,
                                                                                      'label' => $this->l('Disabled', 'ps_legalcompliance'),
                                                                                ),
                                                             ),
                                                       ),
                                                       array('type' => 'switch',
                                                             'label' => $this->l('Price per unit label', 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_UNIT_PRICE',
                                                             'is_bool' => true,
                                                             'desc' => $this->l('If available, displays the price per unit everywhere the product price is listed.', 'ps_legalcompliance'),
                                                             'values' => array(array('id' => 'active_on',
                                                                                      'value' => true,
                                                                                      'label' => $this->l('Enabled', 'ps_legalcompliance'),
                                                                                ),
                                                                                array('id' => 'active_off',
                                                                                      'value' => false,
                                                                                      'label' => $this->l('Disabled', 'ps_legalcompliance'),
                                                                                ),
                                                             ),
                                                       ),
                                                       array('type' => 'switch',
                                                             'label' => $this->l('\'Shipping fees excl.\' label', 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_SHIPPING_INC_EXC',
                                                             'is_bool' => true,
                                                             'desc' => $this->l('Displays a label next to the product price (\'Shipping excluded\') and adds a short mention in the footer of other pages.', 'ps_legalcompliance'),
                                                             'hint' => $this->l('If enabled, make sure the Shipping terms are associated with a page below (Legal Content Management). The label will link to this content.', 'ps_legalcompliance'),
                                                             'values' => array(
                                                                 array(
                                                                     'id' => 'active_on',
                                                                     'value' => true,
                                                                     'label' => $this->l('Enabled', 'ps_legalcompliance'),
                                                                 ),
                                                                 array(
                                                                     'id' => 'active_off',
                                                                     'value' => false,
                                                                     'label' => $this->l('Disabled', 'ps_legalcompliance'),
                                                                 ),
                                                             ),
                                                       ),
                                                       array(
                                                           'type' => 'switch',
                                                           'label' => $this->l('Revocation Terms within ToS', 'ps_legalcompliance'),
                                                           'name' => 'AEUC_LABEL_REVOCATION_TOS',
                                                           'is_bool' => true,
                                                           'desc' => $this->l('Includes content from the Revocation Terms page within the Terms of Services (ToS).', 'ps_legalcompliance'),
                                                           'hint' => $this->l('If enabled, make sure the Revocation Terms are associated with a page below (Legal Content Management).', 'ps_legalcompliance'),
                                                           'disable' => true,
                                                           'values' => array(
                                                               array(
                                                                   'id' => 'active_on',
                                                                   'value' => true,
                                                                   'label' => $this->l('Enabled', 'ps_legalcompliance'),
                                                               ),
                                                               array(
                                                                   'id' => 'active_off',
                                                                   'value' => false,
                                                                   'label' => $this->l('Disabled', 'ps_legalcompliance'),
                                                               ),
                                                           ),
                                                       ),
                                                       array(
                                                           'type' => 'switch',
                                                           'label' => $this->l('Revocation for virtual products', 'ps_legalcompliance'),
                                                           'name' => 'AEUC_LABEL_REVOCATION_VP',
                                                           'is_bool' => true,
                                                           'desc' => $this->l('Adds a mandatory checkbox when the cart contains a virtual product. Use it to ensure customers are aware that a virtual product cannot be returned.', 'ps_legalcompliance'),
                                                           'hint' => $this->l('Require customers to renounce their revocation right when purchasing virtual products (digital goods or services).', 'ps_legalcompliance'),
                                                           'disable' => true,
                                                           'values' => array(
                                                               array(
                                                                   'id' => 'active_on',
                                                                   'value' => true,
                                                                   'label' => $this->l('Enabled', 'ps_legalcompliance'),
                                                               ),
                                                               array(
                                                                   'id' => 'active_off',
                                                                   'value' => false,
                                                                   'label' => $this->l('Disabled', 'ps_legalcompliance'),
                                                               ),
                                                           ),
                                                       ),
                                                       array(
                                                           'type' => 'switch',
                                                           'label' => $this->l('\'From\' price label (when combinations)'),
                                                           'name' => 'AEUC_LABEL_COMBINATION_FROM',
                                                           'is_bool' => true,
                                                           'desc' => $this->l('Displays a \'From\' label before the price on products with combinations.', 'ps_legalcompliance'),
                                                           'hint' => $this->l('As prices can vary from a combination to another, this label indicates that the final price may be higher.', 'ps_legalcompliance'),
                                                           'disable' => true,
                                                           'values' => array(
                                                               array(
                                                                   'id' => 'active_on',
                                                                   'value' => true,
                                                                   'label' => $this->l('Enabled', 'ps_legalcompliance'),
                                                               ),
                                                               array(
                                                                   'id' => 'active_off',
                                                                   'value' => false,
                                                                   'label' => $this->l('Disabled', 'ps_legalcompliance'),
                                                               ),
                                                           ),
                                                       ),
                                                       array('type' => 'text',
                                                             'lang' => true,
                                                             'label' => $this->l('Custom text in shopping cart page',
                                                                                 'ps_legalcompliance'),
                                                             'name' => 'AEUC_LABEL_CUSTOM_CART_TEXT',
                                                             'desc' => $this->l('This text will be displayed on the shopping cart page. Leave empty to disable.', 'ps_legalcompliance'),
                                                             'hint' => $this->l('Please inform your customers about how the order is legally confirmed.', 'ps_legalcompliance'),
                                                       ),
                                     ),
                                     'submit' => array(
                                         'title' => $this->l('Save', 'ps_legalcompliance'),
                                     ),
        ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormLabelsManagerValues()
    {
        $delivery_time_available_values = array();
        $delivery_time_oos_values = array();
        $custom_cart_text_values = array();

        $langs = Language::getLanguages(false, false);

        foreach ($langs as $lang) {
            $tmp_id_lang = (int) $lang['id_lang'];
            $delivery_time_available_values[$tmp_id_lang] = Configuration::get('AEUC_LABEL_DELIVERY_TIME_AVAILABLE', $tmp_id_lang);
            $delivery_time_oos_values[$tmp_id_lang] = Configuration::get('AEUC_LABEL_DELIVERY_TIME_OOS', $tmp_id_lang);
            $delivery_additional[$tmp_id_lang] = Configuration::get('AEUC_LABEL_DELIVERY_ADDITIONAL', $tmp_id_lang);
            $custom_cart_text_values[$tmp_id_lang] = Configuration::get('AEUC_LABEL_CUSTOM_CART_TEXT', $tmp_id_lang);
        }

        return array(
            'AEUC_LABEL_DELIVERY_TIME_AVAILABLE' => $delivery_time_available_values,
            'AEUC_LABEL_DELIVERY_TIME_OOS' => $delivery_time_oos_values,
            'AEUC_LABEL_DELIVERY_ADDITIONAL' => $delivery_additional,
            'AEUC_LABEL_CUSTOM_CART_TEXT' => $custom_cart_text_values,
            'AEUC_LABEL_SPECIFIC_PRICE' => Configuration::get('AEUC_LABEL_SPECIFIC_PRICE'),
            'AEUC_LABEL_UNIT_PRICE' => Configuration::get('AEUC_LABEL_UNIT_PRICE'),
            'AEUC_LABEL_TAX_INC_EXC' => Configuration::get('AEUC_LABEL_TAX_INC_EXC'),
            'AEUC_LABEL_REVOCATION_TOS' => Configuration::get('AEUC_LABEL_REVOCATION_TOS'),
            'AEUC_LABEL_REVOCATION_VP' => Configuration::get('AEUC_LABEL_REVOCATION_VP'),
            'AEUC_LABEL_SHIPPING_INC_EXC' => Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC'),
            'AEUC_LABEL_COMBINATION_FROM' => Configuration::get('AEUC_LABEL_COMBINATION_FROM'),
        );
    }

    /**
     * Create the form that will let user choose all the wording options.
     */
    protected function renderFormFeaturesManager()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAEUC_featuresManager';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                                .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormFeaturesManagerValues(),
            /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigFormFeaturesManager()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormFeaturesManager()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Features', 'ps_legalcompliance'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable \'Reordering\' feature', 'ps_legalcompliance'),
                        'hint' => $this->l('If enabled, the \'Reorder\' option allows customers to reorder in one click from their Order History page.', 'ps_legalcompliance'),
                        'name' => 'AEUC_FEAT_REORDER',
                        'is_bool' => true,
                        'desc' => $this->l('Make sure you comply with your local legislation before enabling: it can be considered as unsolicited goods.', 'ps_legalcompliance'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled', 'ps_legalcompliance'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled', 'ps_legalcompliance'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Proportionate tax for shipping and wrapping', 'ps_legalcompliance'),
                        'name' => 'PS_ATCP_SHIPWRAP',
                        'is_bool' => true,
                        'desc' => $this->l('When enabled, tax for shipping and wrapping costs will be calculated proportionate to taxes applying to the products in the cart.',
                                              'ps_legalcompliance'),
                        'hint' => $this->l('If active, your carriers\' shipping fees must be tax included! Make sure it is the case in the Shipping section.', 'ps_legalcompliance'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled', 'ps_legalcompliance'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled', 'ps_legalcompliance'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save', 'ps_legalcompliance'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormFeaturesManagerValues()
    {
        return array(
            'AEUC_FEAT_REORDER' => !Configuration::get('PS_DISALLOW_HISTORY_REORDERING'),
            'PS_ATCP_SHIPWRAP' => Configuration::get('PS_ATCP_SHIPWRAP'),
        );
    }

    /**
     * Create the form that will let user manage his legal page trough "CMS" feature.
     */
    protected function renderFormLegalContentManager()
    {
        $cms_roles_aeuc = $this->getCMSRoles();
        $cms_repository = $this->entity_manager->getRepository('CMS');
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_roles = $cms_role_repository->findByName(array_keys($cms_roles_aeuc));
        $cms_roles_assoc = array();
        $id_lang = Context::getContext()->employee->id_lang;
        $id_shop = Context::getContext()->shop->id;

        foreach ($cms_roles as $cms_role) {
            if ((int) $cms_role->id_cms > 0) {
                $cms_entity = $cms_repository->findOne((int) $cms_role->id_cms);
                $assoc_cms_name = $cms_entity->meta_title[(int) $id_lang];
            } else {
                $assoc_cms_name = $this->l('-- Select associated page --', 'ps_legalcompliance');
            }

            $cms_roles_assoc[(int) $cms_role->id] = array('id_cms' => (int) $cms_role->id_cms,
                                                         'page_title' => (string) $assoc_cms_name,
                                                         'role_title' => (string) $cms_roles_aeuc[$cms_role->name],
            );
        }

        $cms_pages = $cms_repository->i10nFindAll($id_lang, $id_shop);
        $fake_object = new stdClass();
        $fake_object->id = 0;
        $fake_object->meta_title = $this->l('-- Select associated page --', 'ps_legalcompliance');
        $cms_pages[-1] = $fake_object;
        unset($fake_object);

        $this->context->smarty->assign(array(
                                           'cms_roles_assoc' => $cms_roles_assoc,
                                           'cms_pages' => $cms_pages,
                                           'form_action' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name,
                                           'add_cms_link' => $this->context->link->getAdminLink('AdminCMS'),
                                       ));

        return $this->display(__FILE__, 'views/templates/admin/legal_cms_manager_form.tpl');
    }

    protected function renderFormEmailAttachmentsManager()
    {
        $cms_roles_aeuc = $this->getCMSRoles();
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_roles_associated = $cms_role_repository->getCMSRolesAssociated();
        $legal_options = array();
        $cleaned_mails_names = array();

        foreach ($cms_roles_associated as $role) {
            $list_id_mail_assoc = AeucCMSRoleEmailEntity::getIdEmailFromCMSRoleId((int) $role->id);
            $clean_list = array();

            foreach ($list_id_mail_assoc as $list_id_mail_assoc) {
                $clean_list[] = $list_id_mail_assoc['id_mail'];
            }

            $legal_options[$role->name] = array(
                'name' => $cms_roles_aeuc[$role->name],
                'id' => $role->id,
                'list_id_mail_assoc' => $clean_list,
            );
        }

        foreach (AeucEmailEntity::getAll() as $email) {
            $cleaned_mails_names[] = $email;
        }

        $this->context->smarty->assign(array(
                                           'has_assoc' => $cms_roles_associated,
                                           'mails_available' => $cleaned_mails_names,
                                           'legal_options' => $legal_options,
                                           'form_action' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name,
                                       ));

        // Insert JS in the page
        $this->context->controller->addJS(($this->_path).'views/js/email_attachement.js');

        return $this->display(__FILE__, 'views/templates/admin/email_attachments_form.tpl');
    }
}
