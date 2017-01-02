<?php

class LinkBlockRepository
{
    private $db;
    private $shop;
    private $db_prefix;

    public function __construct(Db $db, Shop $shop)
    {
        $this->db = $db;
        $this->shop = $shop;
        $this->db_prefix = $db->getPrefix();
    }

    public function createTables()
    {
        $engine = _MYSQL_ENGINE_;
        $success = true;
        $this->dropTables();

        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block`(
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
    			`id_hook` int(1) unsigned DEFAULT NULL,
    			`position` int(10) unsigned NOT NULL default '0',
    			`content` text default NULL,
    			PRIMARY KEY (`id_link_block`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block_lang`(
    			`id_link_block` int(10) unsigned NOT NULL,
    			`id_lang` int(10) unsigned NOT NULL,
    			`name` varchar(40) NOT NULL default '',
    			PRIMARY KEY (`id_link_block`, `id_lang`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block_shop` (
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
    			`id_shop` int(10) unsigned NOT NULL,
    			PRIMARY KEY (`id_link_block`, `id_shop`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8"
        ];

        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return $success;
    }

    public function dropTables()
    {
        $sql = "DROP TABLE IF EXISTS
			`{$this->db_prefix}link_block`,
			`{$this->db_prefix}link_block_lang`,
			`{$this->db_prefix}link_block_shop`";

        return Db::getInstance()->execute($sql);
    }

    public function getCMSBlocksSortedByHook($id_shop = null, $id_lang = null)
    {
        $id_lang = (int) (($id_lang) ?: Context::getContext()->language->id);
        $id_shop = (int) (($id_shop) ?: Context::getContext()->shop->id);

        $sql = 'SELECT
                bc.`id_link_block`,
                bcl.`name` as block_name,
                bc.`id_hook`,
                h.`name` as hook_name,
                h.`title` as hook_title,
                h.`description` as hook_description,
                bc.`position`
            FROM `'._DB_PREFIX_.'link_block` bc
                INNER JOIN `'._DB_PREFIX_.'link_block_lang` bcl
                    ON (bc.`id_link_block` = bcl.`id_link_block`)
                LEFT JOIN `'._DB_PREFIX_.'hook` h
                    ON (bc.`id_hook` = h.`id_hook`)
            WHERE bcl.`id_lang` = '.$id_lang.'
            ORDER BY bc.`position`';

        $blocks = Db::getInstance()->executeS($sql);

        $orderedBlocks = array();
        foreach ($blocks as $block) {
            if (!isset($orderedBlocks[$block['id_hook']])) {
                $id_hook = ($block['id_hook']) ?: 'not_hooked';
                $orderedBlocks[$id_hook] = array(
                    'id_hook' => $block['id_hook'],
                    'hook_name' => $block['hook_name'],
                    'hook_title' => $block['hook_title'],
                    'hook_description' => $block['hook_description'],
                    'blocks' => array(),
                );
            }
        }

        foreach ($blocks as $block) {
            $id_hook = ($block['id_hook']) ?: 'not_hooked';
            unset($block['id_hook']);
            unset($block['hook_name']);
            unset($block['hook_title']);
            unset($block['hook_description']);
            $orderedBlocks[$id_hook]['blocks'][] = $block;
        }

        return $orderedBlocks;
    }

    public function getDisplayHooksForHelper()
    {
        $sql = "SELECT h.id_hook as id, h.name as name
                FROM {$this->db_prefix}hook h
                WHERE (lower(h.`name`) LIKE 'display%')
                ORDER BY h.name ASC
            ";
        $hooks = $this->db->executeS($sql);

        foreach ($hooks as $key => $hook) {
            if (preg_match('/admin/i', $hook['name'])
                || preg_match('/backoffice/i', $hook['name'])) {
                unset($hooks[$key]);
            }
        }
        return $hooks;
    }

    public function getByIdHook($id_hook)
    {
        $id_hook = (int) $id_hook;

        $sql = "SELECT cb.`id_link_block`
                    FROM {$this->db_prefix}link_block cb
                    WHERE `id_hook` = $id_hook
                ";
        $ids = $this->db->executeS($sql);

        $cmsBlock = array();
        foreach ($ids as $id) {
            $cmsBlock[] = new LinkBlock((int)$id['id_link_block']);
        }

        return $cmsBlock;
    }

    public function getCmsPages($id_lang = null)
    {
        $id_lang = (int) (($id_lang) ?: Context::getContext()->language->id);
        $this->shop->id = (int) $this->shop->id;

        $categories = "SELECT  cc.`id_cms_category`,
                        ccl.`name`,
                        ccl.`description`,
                        ccl.`link_rewrite`,
                        cc.`id_parent`,
                        cc.`level_depth`,
                        NULL as pages
            FROM {$this->db_prefix}cms_category cc
            INNER JOIN {$this->db_prefix}cms_category_lang ccl
                ON (cc.`id_cms_category` = ccl.`id_cms_category`)
            INNER JOIN {$this->db_prefix}cms_category_shop ccs
                ON (cc.`id_cms_category` = ccs.`id_cms_category`)
            WHERE `active` = 1
                AND ccl.`id_lang`= $id_lang
                AND ccs.`id_shop`= {$this->shop->id}
        ";

        $pages = $this->db->executeS($categories);

        foreach ($pages as &$category) {
            $category['pages'] =
                $this->db->executeS("SELECT c.`id_cms`,
                        c.`position`,
                        cl.`meta_title` as title,
                        cl.`meta_description` as description,
                        cl.`link_rewrite`
                    FROM {$this->db_prefix}cms c
                    INNER JOIN {$this->db_prefix}cms_lang cl
                        ON (c.`id_cms` = cl.`id_cms`)
                    INNER JOIN {$this->db_prefix}cms_shop cs
                        ON (c.`id_cms` = cs.`id_cms`)
                    WHERE c.`active` = 1
                        AND c.`id_cms_category` = {$category['id_cms_category']}
                        AND cl.`id_lang` = $id_lang
                        AND cs.`id_shop` = {$this->shop->id}
                ");
        }

        return $pages;
    }

    public function getProductPages($id_lang = null)
    {
        $products = array();
        $productPages = array(
            'prices-drop',
            'new-products',
            'best-sales',
        );

        foreach ($productPages as $productPage) {
            $meta = Meta::getMetaByPage($productPage, ($id_lang) ? (int)$id_lang : (int)Context::getContext()->language->id);
            $products[] = array(
                'id_cms' => $productPage,
                'title' => $meta['title'],
            );
        }

        $pages[]['pages'] = $products;

        return $pages;
    }

    public function getStaticPages($id_lang = null)
    {
        $statics = array();
        $staticPages = array(
            'contact',
            'sitemap',
            'stores',
            'authentication',
            'my-account',
        );

        foreach ($staticPages as $staticPage) {
            $meta = Meta::getMetaByPage($staticPage, ($id_lang) ? (int)$id_lang : (int)Context::getContext()->language->id);
            $statics[] = [
                'id_cms' => $staticPage,
                'title' => $meta['title'],
            ];
        }

        $pages[]['pages'] = $statics;

        return $pages;
    }

    public function getCountByIdHook($id_hook)
    {
        $id_hook = (int) $id_hook;

        $sql = "SELECT COUNT(*) FROM {$this->db_prefix}link_block
                    WHERE `id_hook` = $id_hook";

        return Db::getInstance()->getValue($sql);
    }

    public function installFixtures()
    {
        $success = true;
        $id_hook = (int)Hook::getIdByName('displayFooter');

        $queries = [
            'INSERT INTO `'._DB_PREFIX_.'link_block` (`id_link_block`, `id_hook`, `position`, `content`) VALUES
                (1, '.$id_hook.', 1, \'{"cms":[false],"product":["prices-drop","new-products","best-sales"],"static":[false]}\'),
                (2, '.$id_hook.', 2, \'{"cms":["1","2","3","4","5"],"product":[false],"static":["contact","sitemap","stores"]}\');'
        ];

        foreach (Language::getLanguages(true, Context::getContext()->shop->id) as $lang) {
            $queries[] = 'INSERT INTO `'._DB_PREFIX_.'link_block_lang` (`id_link_block`, `id_lang`, `name`) VALUES
                (1,'.$lang['id_lang'].', \'Products\'),
                (2, '.$lang['id_lang'].', \'Our company\')'
            ;
        }

        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return $success;
    }
}
