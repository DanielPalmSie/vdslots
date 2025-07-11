<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddSportsBettingHistoryInMenusOnMrVegas extends Seeder
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
            ->where('alias', '=', 'sports-betting-history')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/account/sports-betting-history')
            ->exists();

        if (!$isPageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $this->getPageParentID(),
                'alias' => 'sports-betting-history',
                'filename' => 'diamondbet/generic.php',
                'cached_path' => '/account/sports-betting-history',
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
            ->where('box_class', '=', 'AccountBox')
            ->where('page_id', '=', $this->getPageID())
            ->exists();

        if (!$isBoxExists) {
            $this->connection->table($this->tableBoxes)->insert([
                'container' => 'full',
                'box_class' => 'AccountBox',
                'priority' => 1,
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
            ->where('name', '=', 'landing_bkg')
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $this->getPageID(),
                'name' => 'landing_bkg',
                'value' => 'MV-BG.jpg'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Menu record process
        |--------------------------------------------------------------------------
        */
        $isMenuExists = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'sports-betting-history')
            ->where('name', '=', '#sports-betting-history')
            ->where('check_permission', '=', 0)
            ->exists();

        if (!$isMenuExists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $this->getParentMenuID(),
                'alias' => 'sports-betting-history',
                'name' => '#sports-betting-history',
                'priority' => 229,
                'link_page_id' => $this->getParentMenuID(),
                'link' => '',
                'getvariables' => '[user/]sports-betting-history/',
                'included_countries' => '',
                'excluded_countries' => 'ES IT DE DK NL SE',
                'icon' => '',
                'check_permission' => 0
            ]);
        }
    }

    private function getPageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'account')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/account')
            ->first();

        return (int)$page->page_id;
    }

    private function getPageID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/account')
            ->first();

        return (int)$page->page_id;
    }

    private function getParentMenuID(): int
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'account-menu')
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
            ->where('alias', '=', 'sports-betting-history')
            ->where('name', '=', '#sports-betting-history')
            ->where('priority', '=', 229)
            ->where('check_permission', '=', 0)
            ->delete();

        $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID())
            ->where('name', '=', 'landing_bkg')
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'sports-betting-history')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/account/sports-betting-history')
            ->delete();

        $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'AccountBox')
            ->where('page_id', '=', $this->getPageID())
            ->delete();
    }
}
