<?php
/**
 * 2007-2016 PrestaShop
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
 *  @author 	PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2016 PrestaShop SA
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class AeucEmailEntity extends ObjectModel
{
	/** @var integer id_mail */
	public $id_mail;
	/** @var string filename */
	public $filename;
	/** @var string display_name */
	public $display_name;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'aeuc_email',
		'primary' => 'id',
		'fields' => array(
			'id_mail'		=> 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'filename' 		=> 	array('type' => self::TYPE_STRING, 'required' => true, 'size' => 64),
			'display_name' 	=> 	array('type' => self::TYPE_STRING, 'required' => true, 'size' => 64),
		),
	);

	/**
	 * Return the complete email collection from DB
	 * @return array|false
	 * @throws PrestaShopDatabaseException
	 */
	public static function getAll()
	{
		$sql = '
		SELECT *
		FROM `'._DB_PREFIX_.AeucEmailEntity::$definition['table'].'`';

		return Db::getInstance()->executeS
		($sql);
	}

	public static function getMailIdFromTplFilename($tpl_name)
	{
		$sql = '
		SELECT `id_mail`
		FROM `'._DB_PREFIX_.AeucEmailEntity::$definition['table'].'`
		WHERE `filename` = "'.pSQL($tpl_name).'"';

		return Db::getInstance()->getRow($sql);
	}
}