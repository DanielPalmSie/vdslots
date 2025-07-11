<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddMobileSportsLiveInMenusOnMrVegas extends Seeder
{
    private string $tablePages;
    private string $tableMenus;
    private string $tableBoxes;
    private string $tablePageSettings;
    private Connection $connection;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableMenus = 'menus';
        $this->tableBoxes = 'boxes';
        $this->tablePageSettings = 'page_settings';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {

        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Page record process
        |--------------------------------------------------------------------------
        */
        $isPageExists = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'live')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/sports/live')
            ->exists();

        if (!$isPageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $this->getPageParentID(),
                'alias' => 'live',
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile/sports/live',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Box record process
        |--------------------------------------------------------------------------
        */
        $isBoxExists = $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'SportsbookBox')
            ->where('page_id', '=', $this->getPageID())
            ->exists();

        if (!$isBoxExists) {
            $this->connection->table($this->tableBoxes)->insert([
                'container' => 'full',
                'box_class' => 'SportsbookBox',
                'priority' => 0,
                'page_id' => $this->getPageID(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Page setting record process
        |--------------------------------------------------------------------------
        */
        $isPageSettingExists = $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID())
            ->where('name', '=', 'hide_bottom')
            ->where('value', '=', 1)
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $this->getPageID(),
                'name' => 'hide_bottom',
                'value' => 1
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Menu record process
        |--------------------------------------------------------------------------
        */
        $isMenuExists = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-secondary-top-menu-sports')
            ->where('name', '=', 'menu.secondary.sportsbook-live')
            ->where('check_permission', '=', 0)
            ->exists();

        if (!$isMenuExists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $this->getParentMenuID(),
                'alias' => 'mobile-secondary-top-menu-sports',
                'name' => 'menu.secondary.sportsbook-live',
                'priority' => 396,
                'link_page_id' => $this->getPageID(),
                'link' => '',
                'getvariables' => '',
                'included_countries' => '',
                'excluded_countries' => 'ES IT DE DK NL SE',
                'icon' => 'icon-vs-live-sportsbook',
                'check_permission' => 0
            ]);
        }
    }

    private function getPageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'sports')
            ->where('cached_path', '=', '/mobile/sports')
            ->first();

        return (int)$page->page_id;
    }

    private function getPageID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/sports/live')
            ->first();

        return (int)$page->page_id;
    }

    private function getParentMenuID(): int
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'mobile-secondary-top-menu')
            ->first();

        return (int)$menu->menu_id;
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-secondary-top-menu-sports')
            ->where('name', '=', 'menu.secondary.sportsbook-live')
            ->where('priority', '=', 396)
            ->where('check_permission', '=', 0)
            ->delete();

        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'SportsbookBox')
            ->where('page_id', '=', $this->getPageID())
            ->delete();

        $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID())
            ->where('name', '=', 'hide_bottom')
            ->where('value', '=', 1)
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'live')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/sports/live')
            ->delete();
    }
}
