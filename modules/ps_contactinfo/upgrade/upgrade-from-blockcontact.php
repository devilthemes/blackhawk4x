<?php

if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_2_0($object)
{
	return Configuration::deleteByName('BLOCKCONTACT_TELNUMBER')
        && Configuration::deleteByName('BLOCKCONTACT_EMAIL');
}
