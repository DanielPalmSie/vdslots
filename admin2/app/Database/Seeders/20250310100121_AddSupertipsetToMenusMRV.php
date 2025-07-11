<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

/**
 * ./console seeder:up 20250310100121
 * 
 * ./console seeder:down 20250310100121
 */
class AddSupertipsetToMenusMRV extends Seeder
{
    /** @var string */
    protected $tablePages;
    protected $tableMenus;
    protected $tableBoxes;
    protected $tablePageSettings;
    protected $tableStartGo;
    protected $brand;

    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->tablePages = 'pages';
        $this->tableMenus = 'menus';
        $this->tableBoxes = 'boxes';
        $this->tablePageSettings = 'page_settings';
        $this->tableStartGo = 'start_go';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        if ($this->brand !== 'mrvegas') {
            return;
        }

        /** Standard page */
        $exists = $this->connection->table($this->tablePages)
            ->where('parent_id', 0)
            ->where('alias', 'super')
            ->exists();
        if (!$exists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => 0,
                'alias' => 'super',
                'filename' => 'diamondbet/generic.php',
                'cached_path' => '/super',
            ]);
        }

        /** Mobile page */
        $mobileRootId = $this->getMobileRootPageId();
        $exists = $this->connection->table($this->tablePages)
            ->where('parent_id', $mobileRootId)
            ->where('alias', 'super')
            ->exists();
        if (!$exists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $mobileRootId,
                'alias' => 'super',
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile/super',
            ]);
        }

        /** Standard page box */
        $standardPageId = $this->getStandardPageID();
        $exists = $this->connection->table($this->tableBoxes)
            ->where('box_class', 'PoolXBox')
            ->where('page_id', $standardPageId)
            ->exists();
        if (!$exists) {
            $this->connection->table($this->tableBoxes)->insert([
                'container' => 'full',
                'box_class' => 'PoolXBox',
                'priority' => 0,
                'page_id' => $standardPageId,
            ]);
        }

        /** Mobile page box */
        $mobilePageId = $this->getMobilePageID();
        $exists = $this->connection->table($this->tableBoxes)
            ->where('box_class', 'PoolXBox')
            ->where('page_id', $mobilePageId)
            ->exists();
        if (!$exists) {
            $this->connection->table($this->tableBoxes)->insert([
                'container' => 'full',
                'box_class' => 'PoolXBox',
                'priority' => 0,
                'page_id' => $mobilePageId,
            ]);
        }

        /** Standard page menu */
        $parentStandardMenuId = $this->getParentStandardMenuID();
        $exists = $this->connection->table($this->tableMenus)
            ->where('alias', 'poolx')
            ->where('parent_id', $parentStandardMenuId)
            ->exists();
        if (!$exists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $parentStandardMenuId,
                'alias' => 'poolx',
                'name' => '#menu.secondary.poolx',
                'priority' => 419,
                'link_page_id' => $standardPageId,
                'link' => '',
                'getvariables' => '',
                'included_countries' => 'SE',
                'excluded_countries' => 'ES IT DE DK NL IE MT GB',
                'icon' => 'icon-supertipset',
                'check_permission' => 0
            ]);
        }

        /** Mobile page menu */
        $parentMobileMenuId = $this->getParentMobileMenuID();
        $exists = $this->connection->table($this->tableMenus)
            ->where('alias', 'mobile-secondary-top-menu-poolx')
            ->where('parent_id', $parentMobileMenuId)
            ->exists();
        if (!$exists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $parentMobileMenuId,
                'alias' => 'mobile-secondary-top-menu-poolx',
                'name' => 'menu.secondary.poolx',
                'priority' => 420,
                'link_page_id' => $mobilePageId,
                'link' => '',
                'getvariables' => '',
                'included_countries' => 'SE',
                'excluded_countries' => 'ES IT DE DK NL IE MT GB',
                'icon' => 'icon-supertipset',
                'check_permission' => 0
            ]);
        }

        /** Redirects */
        $exists = $this->connection->table($this->tableStartGo)
            ->where('from', '/super')
            ->where('to', '/mobile/super')
            ->exists();
        if (!$exists) {
            $this->connection->table($this->tableStartGo)->insert([
                'from' => '/super',
                'to' => '/mobile/super',
            ]);
        }
    }

    public function down()
    {
        if ($this->brand !== 'mrvegas') {
            return;
        }

        /** Delete standard page */
        $this->connection->table($this->tablePages)
            ->where('cached_path', '=', '/super')
            ->delete();

        /** Delete mobile page */
        $this->connection->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/super')
            ->delete();

        /** Delete page boxes */
        $this->connection->table($this->tableBoxes)
            ->where('box_class', '=', 'PoolXBox')
            ->delete();

        /** Delete standard page menu */
        $this->connection->table($this->tableMenus)
            ->where('alias', '=', 'poolx')
            ->delete();

        /** Delete mobile page menu */
        $this->connection->table($this->tableMenus)
            ->where('alias', '=', 'mobile-secondary-top-menu-poolx')
            ->delete();

        /** Delete redirects */
        $this->connection->table($this->tableStartGo)
            ->where('from', '=', '/super')
            ->delete();
    }

    private function getMobileRootPageId(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'mobile')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int)$page->page_id;
    }

    private function getMobilePageID()
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/super')
            ->first();

        return (int)$page->page_id;
    }

    private function getStandardPageID()
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/super')
            ->first();

        return (int)$page->page_id;
    }

    private function getParentStandardMenuID()
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'secondary-top-menu')
            ->first();

        return (int)$menu->menu_id;
    }

    private function getParentMobileMenuID()
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-secondary-top-menu')
            ->first();

        return (int)$menu->menu_id;
    }
}
