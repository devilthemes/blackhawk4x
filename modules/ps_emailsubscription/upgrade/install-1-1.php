<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1($module)
{
    $hook_to_remove_id = Hook::getIdByName('displayAfterBodyOpeningTag');
    if ($hook_to_remove_id) {
        $module->unregisterHook((int)$hook_to_remove_id);
    }

    return Configuration::deleteByName('NW_CONFIRMATION_OPTIN');
}
