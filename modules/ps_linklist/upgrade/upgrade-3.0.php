<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0($object)
{
    Configuration::deleteByName('FOOTER_CMS');
    Configuration::deleteByName('FOOTER_BLOCK_ACTIVATION');
    Configuration::deleteByName('FOOTER_POWEREDBY');
    Configuration::deleteByName('FOOTER_PRICE-DROP');
    Configuration::deleteByName('FOOTER_NEW-PRODUCTS');
    Configuration::deleteByName('FOOTER_BEST-SALES');
    Configuration::deleteByName('FOOTER_CONTACT');
    Configuration::deleteByName('FOOTER_SITEMAP');

    Db::getInstance()->execute('DROP TABLE `_DB_PREFIX_`cms_block_page');

    $object->reset();

    return true;
}
