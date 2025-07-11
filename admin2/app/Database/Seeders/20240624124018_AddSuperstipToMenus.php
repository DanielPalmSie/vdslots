<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddSuperstipToMenus extends Seeder
{
    /** @var string */
    protected $tablePages;
    protected $tableMenus;
    protected $tableBoxes;
    protected $tablePageSettings;
    protected $tableStartGo;

    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableMenus = 'menus';
        $this->tableBoxes = 'boxes';
        $this->tablePageSettings = 'page_settings';
        $this->tableStartGo = 'start_go';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        /** Standard page */
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => 0,
            'alias' => 'superstip',
            'filename' => 'diamondbet/generic.php',
            'cached_path' => '/superstip',
        ]);

        /** Mobile page */
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => $this->getMobileRootPageId(),
            'alias' => 'superstip',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/superstip',
        ]);

        /** Standard page box */
        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'PoolXBox',
            'priority' => 0,
            'page_id' => $this->getStandardPageID(),
        ]);

        /** Mobile page box */
        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'PoolXBox',
            'priority' => 0,
            'page_id' => $this->getMobilePageID(),
        ]);

        /** Standard page menu */
        $this->connection->table($this->tableMenus)->insert([
            'parent_id' => $this->getParentStandardMenuID(),
            'alias' => 'poolx',
            'name' => '#menu.secondary.poolx',
            'priority' => 419,
            'link_page_id' => $this->getStandardPageID(),
            'link' => '',
            'getvariables' => '',
            'included_countries' => 'SE',
            'excluded_countries' => '',
            'icon' => 'icon-vs-sportsbook',
            'check_permission' => 0
        ]);

        /** Mobile page menu */
        $this->connection->table($this->tableMenus)->insert([
            'parent_id' => $this->getParentMobileMenuID(),
            'alias' => 'mobile-secondary-top-menu-poolx',
            'name' => '#menu.secondary.poolx',
            'priority' => 420,
            'link_page_id' => $this->getMobilePageID(),
            'link' => '',
            'getvariables' => '',
            'included_countries' => 'SE',
            'excluded_countries' => '',
            'icon' => 'icon-vs-sportsbook',
            'check_permission' => 0
        ]);

        /** Redirects */
        $this->connection->table($this->tableStartGo)->insert([
            'from' => '/superstip',
            'to' => '/mobile/superstip',
        ]);
    }

    public function down()
    {
        /** Delete standard page */
        $this->connection->table($this->tablePages)
            ->where('cached_path', '=', '/superstip')
            ->delete();

        /** Delete mobile page */
        $this->connection->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/superstip')
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
            ->where('from', '=', '/superstip')
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
            ->where('cached_path', '=', '/mobile/superstip')
            ->first();

        return (int)$page->page_id;
    }

    private function getStandardPageID()
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/superstip')
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
