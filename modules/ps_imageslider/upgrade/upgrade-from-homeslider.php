<?php

if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_2_0_0($object)
{
    return ($object->unregisterHook('displayTopColumn')
        && $object->registerHook('displayHome')
        && Configuration::deleteByName('HOMESLIDER_PAUSE')
        && Configuration::deleteByName('HOMESLIDER_LOOP')
    );
}
