<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddLiveSportsIconInMenusTable extends Migration
{
    /** @var string */
    protected $tablePages;
    protected $tableMenus;
    protected $tableBoxes;
    protected $tablePageSettings;

    /** @var Connection */
    protected $connection;

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
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => $this->getPagesParentID(),
            'alias' => 'live',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/sports/live',
        ]);

        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'SportsbookBox',
            'priority' => 0,
            'page_id' => $this->getPageID(),
        ]);

        $this->connection->table($this->tablePageSettings)->insert([
            'page_id' => $this->getPageID(),
            'name' => 'hide_bottom',
            'value' => 1
        ]);

        $this->connection->table($this->tableMenus)->insert([
            'parent_id' => $this->getParentMenuID(),
            'alias' => 'mobile-secondary-top-menu-sports',
            'name' => 'menu.secondary.sportsbook-live',
            'priority' => 391,
            'link_page_id' => $this->getPageID(),
            'link' => '',
            'getvariables' => '',
            'included_countries' => '',
            'excluded_countries' => 'GB ES IT DE DK',
            'icon' => 'icon-vs-live-sportsbook',
            'check_permission' => 0
        ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->tableMenus)
            ->where('parent_id', '=', $this->getParentMenuID())
            ->where('alias', '=', 'mobile-secondary-top-menu-sports')
            ->where('name', '=', 'menu.secondary.sportsbook-live')
            ->where('priority', '=', 391)
            ->where('link_page_id', '=', $this->getPageID())
            ->where('icon', '=', 'icon-vs-live-sportsbook')
            ->where('check_permission', '=', 0 )
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
            ->where('parent_id', '=', $this->getPagesParentID())
            ->where('alias', '=', 'live')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/sports/live')
            ->delete();
    }

    private function getPageID()
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/sports/live')
            ->first();

        return (int)$page->page_id;
    }

    private function getParentMenuID()
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-secondary-top-menu')
            ->first();

        return (int)$menu->menu_id;
    }

    private function getPagesParentID()
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/sports')
            ->first();

        return (int)$page->page_id;
   }
}
