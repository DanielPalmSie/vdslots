<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddSubMenusInMenusTable extends Migration
{
    private string $menus_table = 'menus';
    private string $pages_table = 'pages';
    private Connection $connection;

    private array $sub_menu_pages = [
        [
            'parent_id' => 0,
            'alias' => 'live-casino',
            'filename' => 'diamondbet/generic.php',
            'cached_path' => '/live-casino'
        ],
        [
            'parent_id' => 0,
            'alias' => 'jackpots',
            'filename' => 'diamondbet/generic.php',
            'cached_path' => '/jackpots'
        ]
    ];

    private array $sub_menus = [
        [
            'parent_id' => 0,
            'alias' => 'casino',
            'name' => '#menu.secondary.casino',
            'priority' => 388,
            'link' => '',
            'getvariables' => '',
            'new_window' => 0,
            'check_permission' => 0,
            'logged_in' => 0,
            'logged_out' => 0,
            'included_countries' => '',
            'excluded_countries' => 'DE',
            'icon' => 'icon-vs-slot-machine'
        ],
        [
            'parent_id' => 0,
            'alias' => 'live-casino',
            'name' => '#menu.secondary.casino-live',
            'priority' => 389,
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
            'alias' => 'videoslots_jackpot',
            'name' => '#menu.secondary.jackpots',
            'priority' => 390,
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
    }

    /**
     * Do the migration
     * Create pages, parent menu and submenus.
     */
    public function up()
    {
        if (getenv('APP_SHORT_NAME') === 'VS') {
            return;
        }
        $this->createPagesForSubMenu();
        $this->createParentMenu();
        $this->createChildMenus();
    }

    /**
     * Undo the migration
     * Delete submenus, parent menu and pages.
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') === 'VS') {
            return;
        }
        $this->deleteChildMenus();
        $this->deleteParentMenu();
        $this->deletePagesForSubMenu();
    }

    private function createParentMenu()
    {
        $parent_menu = $this->connection->table($this->menus_table)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'secondary-top-menu')
            ->where('priority', '=', '387')
            ->first();

        if (!empty($parent_menu)) {
            return;
        }

        $this->connection->table($this->menus_table)
            ->insert([
                'parent_id' => 0,
                'alias' => 'secondary-top-menu',
                'name' => 'secondary-top-menu',
                'priority' => '387',
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

    private function getParentMenu()
    {
        return $this->connection->table($this->menus_table)
            ->where('alias', '=', 'secondary-top-menu')
            ->first();
    }

    private function createChildMenus()
    {
        $parent_menu = $this->getParentMenu();

        foreach ($this->sub_menus as $menu) {

            $exists = $this->connection->table($this->menus_table)
                ->where('parent_id', '=', $parent_menu->menu_id)
                ->where('alias', '=', $menu['alias'])
                ->where('priority', '=', $menu['priority'])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            $menu['link_page_id'] = $this->getLinkPageId($menu['alias']);
            $menu['parent_id'] = $parent_menu->menu_id;

            $this->connection->table($this->menus_table)->insert($menu);
        }
    }

    private function getLinkPageId($alias)
    {
        if ($alias == 'casino') {
            return $this->getCasinoPageId();
        }

        if ($alias == 'live-casino') {
            return $this->getLiveCasinoPageId();
        }

        if ($alias == 'videoslots_jackpot') {
            return $this->getJackPotPageId();
        }
    }

    private function getCasinoPageId()
    {
        $casino_page = $this->connection->table($this->pages_table)
            ->where('alias', '=', '.')
            ->where('parent_id', '=', 0)
            ->first();

        return $casino_page->page_id;
    }

    private function getLiveCasinoPageId()
    {
        $live_casino_page = $this->connection->table($this->pages_table)
            ->where('alias', '=', 'live-casino')
            ->where('parent_id', '=', 0)
            ->first();

        return $live_casino_page->page_id;
    }

    private function getJackPotPageId()
    {
        $jackpot_page = $this->connection->table($this->pages_table)
            ->where('alias', '=', 'jackpots')
            ->where('parent_id', '=', 0)
            ->first();

        return $jackpot_page->page_id;
    }

    private function createPagesForSubMenu()
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

    private function deleteChildMenus()
    {
        foreach ($this->sub_menus as $menu) {
            $this->connection->table($this->menus_table)
                ->where('alias', '=', $menu['alias'])
                ->where('name', '=', $menu['name'])
                ->where('priority', '=', $menu['priority'])
                ->delete();
        }
    }

    private function deleteParentMenu()
    {
        $parent_menu = $this->getParentMenu();
        $this->connection->table($this->menus_table)
            ->where('menu_id', '=', $parent_menu->menu_id)
            ->delete();
    }

    private function deletePagesForSubMenu()
    {
        foreach ($this->sub_menu_pages as $page) {
            $this->connection->table($this->pages_table)
                ->where('alias', '=', $page['alias'])
                ->where('cached_path', '=', $page['cached_path'])
                ->delete();
        }
    }
}
