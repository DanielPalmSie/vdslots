<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Helpers\SportsbookHelper;

class SbSetupAddMobileSportsHistoryInMenusOnKS extends Seeder
{
    private string $tablePages;
    private string $tableMenus;
    private string $tableBoxes;
    private Connection $connection;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableMenus = 'menus';
        $this->tableBoxes = 'boxes';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (getenv('APP_SHORT_NAME') !== 'KS') {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Page record process
        |--------------------------------------------------------------------------
        */
        $isPageExists = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'account')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/account')
            ->exists();

        if (!$isPageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $this->getPageParentID(),
                'alias' => 'account',
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile/account',
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
                'priority' => 0,
                'page_id' => $this->getPageID(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Menu record process
        |--------------------------------------------------------------------------
        */
        $isMenuExists = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-sports-betting-history')
            ->where('name', '=', '#sports-betting-history')
            ->where('check_permission', '=', 0)
            ->exists();

        if (!$isMenuExists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $this->getParentMenuID(),
                'alias' => 'mobile-sports-betting-history',
                'name' => '#sports-betting-history',
                'priority' => 207,
                'link_page_id' => $this->getPageID(),
                'link' => '',
                'getvariables' => '[user/]sports-betting-history/',
                'included_countries' => '',
                'excluded_countries' => 'ES DE DK MT IT GB NL CA',
                'icon' => '',
                'check_permission' => 0
            ]);
        }
    }

    private function getPageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'mobile')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int)$page->page_id;
    }

    private function getPageID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/account')
            ->first();

        return (int)$page->page_id;
    }

    private function getParentMenuID(): int
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-account-menu')
            ->first();

        return (int)$menu->menu_id;
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'KS') {
            return false;
        }

        $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-sports-betting-history')
            ->where('name', '=', '#sports-betting-history')
            ->where('check_permission', '=', 0)
            ->delete();

        $page = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'account')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/account');
        $this->doPageDelete($page);


        $box = $this->connection
            ->table($this->tableBoxes)
            ->where('container', '=', 'full')
            ->where('box_class', '=', 'AccountBox')
            ->where('page_id', '=', $this->getPageID());
        $this->doBoxDelete($box);

    }

    /**
     * Page must be deleted if no usage has been found
     *
     * @param $page
     * @return void
     */
    private function doPageDelete($page)
    {
        $pageInfo = $page->first();
        if (!empty($pageInfo->page_id)) {
            $pageUsageCount = $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $pageInfo->page_id)
                ->count();

            if ($pageUsageCount === 0) {
                $page->delete();
            }
        }
    }

    /**
     * Box must be deleted if no usage has been found
     *
     * @param $box
     * @return void
     */
    private function doBoxDelete($box)
    {
        $boxInfo = $box->first();
        if (!empty($boxInfo->box_id)) {
            $pageUsageCount = $this->connection
                ->table($this->tablePages)
                ->where('box_id', '=', $boxInfo->box_id)
                ->count();

            if ($pageUsageCount === 0) {
                $box->delete();
            }
        }
    }
}