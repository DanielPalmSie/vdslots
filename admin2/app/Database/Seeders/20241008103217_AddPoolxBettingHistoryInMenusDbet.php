<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddPoolxBettingHistoryInMenusDbet extends Seeder
{
    private string $tablePages;
    private string $tableMenus;
    private Connection $connection;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableMenus = 'menus';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        if (getenv('APP_SHORT_NAME') !== 'DBET') {
            return false;
        }

        /* Page creation for Desktop */
        $isPageExists = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'sports-betting-history-poolx')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/account/sptip-betting-history')
            ->exists();

        if (!$isPageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $this->getPageParentID(),
                'alias' => 'sports-betting-history-poolx',
                'filename' => 'diamondbet/generic.php',
                'cached_path' => '/account/sptip-betting-history',
            ]);
        }

        /* Page creation for Mobile */
        $isMobilePageExists = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getMobilePageParentID())
            ->where('alias', '=', 'account-poolx')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/account-sptip')
            ->exists();

        if (!$isMobilePageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $this->getMobilePageParentID(),
                'alias' => 'account-poolx',
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile/account-sptip',
            ]);
        }

        /* Menu creation for Desktop */
        $isMenuExists = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'sports-betting-history-poolx')
            ->where('name', '=', '#sports-betting-history-poolx')
            ->where('check_permission', '=', 0)
            ->exists();

        if (!$isMenuExists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $this->getParentMenuID(),
                'alias' => 'sports-betting-history-poolx',
                'name' => '#sports-betting-history-poolx',
                'priority' => 229,
                'link_page_id' => $this->getParentMenuID(),
                'link' => '',
                'getvariables' => '[user/]sptip-betting-history/',
                'included_countries' => 'SE',
                'excluded_countries' => '',
                'icon' => '',
                'check_permission' => 0
            ]);
        }

        /* Menu creation for Mobile */
        $isMobileMenuExists = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mob-sports-betting-history-poolx')
            ->where('name', '=', '#sports-betting-history-poolx')
            ->where('check_permission', '=', 0)
            ->exists();

        if (!$isMobileMenuExists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $this->getMobileParentMenuID(),
                'alias' => 'mob-sports-betting-history-poolx',
                'name' => '#sports-betting-history-poolx',
                'priority' => 207,
                'link_page_id' => $this->getPageID(),
                'link' => '',
                'getvariables' => '[user/]sptip-betting-history/',
                'included_countries' => 'SE',
                'excluded_countries' => '',
                'icon' => '',
                'check_permission' => 0
            ]);
        }
    }

    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'DBET') {
            return false;
        }

        /* Desktop */
        $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'sports-betting-history-poolx')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/account/sptip-betting-history')
            ->delete();

        $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'sports-betting-history-poolx')
            ->where('name', '=', '#sports-betting-history-poolx')
            ->where('check_permission', '=', 0)
            ->delete();

        /* Mobile */
        $page = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'account-poolx')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/account-sptip');
        $this->doPageDelete($page);

        $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mob-sports-betting-history-poolx')
            ->where('name', '=', '#sports-betting-history-poolx')
            ->where('check_permission', '=', 0)
            ->delete();
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

    private function getMobilePageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'mobile')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile')
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

    private function getMobileParentMenuID(): int
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-account-menu')
            ->first();

        return (int)$menu->menu_id;
    }

    private function getPageID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/account')
            ->first();

        return (int)$page->page_id;
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
}