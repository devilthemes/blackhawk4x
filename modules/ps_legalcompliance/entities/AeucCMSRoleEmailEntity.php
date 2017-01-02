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

class AeucCMSRoleEmailEntity extends ObjectModel
{
	/** @var string name */
	public $id_cms_role;
	/** @var integer id_cms */
	public $id_mail;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'aeuc_cmsrole_email',
		'primary' => 'id',
		'fields' => array(
			'id_mail'	=> 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'id_cms_role' 	=> 	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
		),
	);

	/**
	 * Truncate Table
	 * @return array|false
	 * @throws PrestaShopDatabaseException
	 */
	public static function truncate()
	{
		$sql = 'TRUNCATE `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`';
		return Db::getInstance()->execute($sql);
	}

	/**
	 * Return the complete list of cms_role_ids associated
	 * @return array|false
	 * @throws PrestaShopDatabaseException
	 */
	public static function getIdEmailFromCMSRoleId($id_cms_role)
	{
		$sql = '
		SELECT `id_mail`
		FROM `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`
		WHERE `id_cms_role` = '.(int)$id_cms_role;

		return Db::getInstance()->executeS($sql);
	}


	/**
	 * Return the complete email collection from DB
	 * @return array|false
	 * @throws PrestaShopDatabaseException
	 */
	public static function getAll()
	{
		$sql = '
		SELECT *
		FROM `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`';

		return Db::getInstance()->executeS($sql);
	}

	public static function getCMSRoleIdsFromIdMail($id_mail)
	{
		$sql = '
		SELECT DISTINCT(`id_cms_role`)
		FROM `'._DB_PREFIX_.AeucCMSRoleEmailEntity::$definition['table'].'`
		WHERE `id_mail` = '.(int)$id_mail;

		return Db::getInstance()->executeS($sql);
	}



}