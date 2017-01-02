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

class LinkBlock extends ObjectModel
{
    public $id_link_block;
    public $name;
    public $id_hook;
    public $position;
    public $content;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'link_block',
        'primary' => 'id_link_block',
        'multilang' => true,
        'fields' => array(
            'name' =>       array('type' => self::TYPE_STRING, 'lang' => true, 'required' => true, 'size' => 128),
            'id_hook' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'position' =>   array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'content' =>    array('type' => self::TYPE_STRING, 'validate' => 'isJson'),
        ),
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);

        if ($this->id) {
            $this->content = json_decode($this->content, true);
        }

        if (is_null($this->content)) {
            $this->content = array(
                'cms' => array(),
                'product' => array(),
                'static' => array(),
            );
        }
    }

    public function add($auto_date = true, $null_values = false)
    {
        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        }

        if (!$this->position) {
            $this->position = 1;
        }

        $return = parent::add($auto_date, $null_values);
        $this->content = json_decode($this->content, true);
        return $return;
    }

    public function update($auto_date = true, $null_values = false)
    {
        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        }

        $return = parent::update($auto_date, $null_values);
        $this->content = json_decode($this->content, true);
        return $return;
    }
}
