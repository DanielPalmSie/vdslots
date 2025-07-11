<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddMobileSubMenusInMenusTable extends Migration
{
    private string $menus_table = 'menus';
    private string $pages_table = 'pages';
    private int $parent_id;
    private Connection $connection;

    private array $sub_menu_pages = [
        [
            'alias' => 'live-casino',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/live-casino'
        ],
        [
            'alias' => 'jackpots',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/jackpots'
        ]
    ];

    private array $sub_menus = [
        [
            'parent_id' => 0,
            'alias' => 'mobile-secondary-top-menu-casino',
            'name' => 'mobile-secondary-top-menu-casino',
            'priority' => 392,
            'link' => '',
            'getvariables' => '',
            'new_window' => 0,
            'check_permission' => 0,
            'logged_in' => 0,
            'logged_out' => 0,
            'included_countries' => '',
            'excluded_countries' => '',
            'icon' => 'icon-vs-slot-machine'
        ],
        [
            'parent_id' => 0,
            'alias' => 'mobile-secondary-top-menu-casino',
            'name' => 'mobile-secondary-top-menu-casino-live',
            'priority' => 393,
            'link' => '',
            'getvariables' => '',
            'new_window' => 0,
            'check_permission' => 0,
            'logged_in' => 0,
            'logged_out' => 0,
            'included_countries' => '',
            'excluded_countries' => 'DE',
            'icon' => 'icon-live-casino'
        ],
        [
            'parent_id' => 0,
            'alias' => 'mobile-secondary-top-menu-casino',
            'name' => 'mobile-secondary-top-menu-casino-jackpot',
            'priority' => 394,
            'link' => '',
            'getvariables' => '',
            'new_window' => 0,
            'check_permission' => 0,
            'logged_in' => 0,
            'logged_out' => 0,
            'included_countries' => '',
            'excluded_countries' => 'DE DK',
            'icon' => 'icon-jackpots'
        ],
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();

        $this->parent_id = $this->getParentId();
        foreach($this->sub_menu_pages as $index => $page) {
            $this->sub_menu_pages[$index]['parent_id'] = $this->parent_id;
        }
    }

    /**
     * Do the migration
     * Create mobile pages, parent menu and submenus.
     */
    public function up()
    {
        if (getenv('APP_SHORT_NAME') === 'VS') {
            return;
        }
        $this->createMobilePagesForSubMenu();
        $this->createMobileParentMenu();
        $this->createMobileChildMenus();
    }

    /**
     * Undo the migration
     * Delete mobile submenus, parent menu and pages.
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') === 'VS') {
            return;
        }
        $this->deleteMobileChildMenus();
        $this->deleteMobileParentMenu();
        $this->deleteMobilePagesForSubMenu();
    }

    private function createMobileParentMenu()
    {
        $parent_menu = $this->connection->table($this->menus_table)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'mobile-secondary-top-menu')
            ->where('priority', '=', '391')
            ->first();

        if (!empty($parent_menu)) {
            return;
        }

        $this->connection->table($this->menus_table)
            ->insert([
                'parent_id' => 0,
                'alias' => 'mobile-secondary-top-menu',
                'name' => 'mobile-secondary-top-menu',
                'priority' => '391',
                'link_page_id' => 0,
                'link' => '',
                'getvariables' => '',
                'new_window' => 0,
                'check_permission' => 0,
                'logged_in' => 0,
                'logged_out' => 0,
                'included_countries' => '',
                'excluded_countries' => '',
                'icon' => ''
            ]);
    }

    private function getMobileParentMenu()
    {
        return $this->connection->table($this->menus_table)
            ->where('alias', '=', 'mobile-secondary-top-menu')
            ->first();
    }

    private function createMobileChildMenus()
    {
        $parent_menu = $this->getMobileParentMenu();

        foreach($this->sub_menus as $menu) {

            $exists = $this->connection->table($this->menus_table)
                ->where('parent_id', '=', $parent_menu->menu_id)
                ->where('alias', '=', $menu['alias'])
                ->where('priority', '=', $menu['priority'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            $menu['link_page_id'] = $this->getMobileLinkPageId($menu['name']);
            $menu['parent_id'] = $parent_menu->menu_id;

            $this->connection->table($this->menus_table)->insert($menu);
        }
    }

    private function getMobileLinkPageId($name)
    {
        if($name == 'mobile-secondary-top-menu-casino') {
            return $this->getMobileCasinoPageId();
        }

        if($name == 'mobile-secondary-top-menu-casino-live') {
            return $this->getMobileLiveCasinoPageId();
        }

        if($name == 'mobile-secondary-top-menu-casino-jackpot') {
            return $this->getMobileJackPotPageId();
        }
    }

    private function getMobileCasinoPageId()
    {
        $casino_page = $this->connection->table($this->pages_table)
            ->where('alias', '=', 'mobile')
            ->where('parent_id', '=', 0)
            ->first();

        return $casino_page->page_id;
    }

    private function getMobileLiveCasinoPageId()
    {
        $live_casino_page = $this->connection->table($this->pages_table)
            ->where('alias', '=','live-casino')
            ->where('parent_id', '=', $this->parent_id)
            ->first();

        return $live_casino_page->page_id;
    }

    private function getMobileJackPotPageId()
    {
        $jackpot_page = $this->connection->table($this->pages_table)
            ->where('alias','=', 'jackpots')
            ->where('parent_id', '=', $this->parent_id)
            ->first();

        return $jackpot_page->page_id;
    }

    private function getParentId(): int
    {
        $page = $this->connection->table($this->pages_table)
            ->where('alias','=', 'mobile')
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int) $page->page_id;
    }

    private function createMobilePagesForSubMenu()
    {
        foreach ($this->sub_menu_pages as $sub_menu_page) {
            $exists = $this->connection->table($this->pages_table)
                ->where('alias', '=', $sub_menu_page['alias'])
                ->where('parent_id', '=', $sub_menu_page['parent_id'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            $this->connection->table($this->pages_table)->insert($sub_menu_page);
        }
    }

    private function deleteMobileChildMenus()
    {
        foreach($this->sub_menus as $menu) {
            $this->connection->table($this->menus_table)
                ->where('alias', '=', $menu['alias'])
                ->where('name', '=', $menu['name'])
                ->where('priority', '=', $menu['priority'])
                ->delete();
        }
    }

    private function deleteMobileParentMenu()
    {
        $parent_menu = $this->getMobileParentMenu();
        $this->connection->table($this->menus_table)
            ->where('menu_id', '=', $parent_menu->menu_id)
            ->delete();
    }

    private function deleteMobilePagesForSubMenu()
    {
        foreach($this->sub_menu_pages as $page) {
            $this->connection->table($this->pages_table)
                ->where('alias', '=', $page['alias'])
                ->where('cached_path', '=', $page['cached_path'])
                ->delete();
        }
    }
}
