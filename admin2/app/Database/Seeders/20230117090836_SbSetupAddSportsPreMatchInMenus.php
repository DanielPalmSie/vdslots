<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Helpers\SportsbookHelper;

class SbSetupAddSportsPreMatchInMenus extends Seeder
{

    private string $tablePages;
    private string $tableMenus;
    private string $tableBoxes;
    private string $tablePageSettings;
    private Connection $connection;
    private bool $shouldRunSeeder;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->tableMenus = 'menus';
        $this->tableBoxes = 'boxes';
        $this->tablePageSettings = 'page_settings';
        $this->connection = DB::getMasterConnection();
        $this->shouldRunSeeder = SportsbookHelper::shouldRunSbSetupSeeder();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->shouldRunSeeder) {
            echo "SB_SETUP_MODE is false. Contents of Seeder will not be seeded... " . PHP_EOL;
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
            ->where('alias', '=', 'prematch')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/sports/prematch')
            ->exists();

        if (!$isPageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $this->getPageParentID(),
                'alias' => 'prematch',
                'filename' => 'diamondbet/generic.php',
                'cached_path' => '/sports/prematch',
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
            ->where('name', '=', 'landing_bkg')
            ->where('value', '=', 'sportsbook_bg.jpg')
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $this->getPageID(),
                'name' => 'landing_bkg',
                'value' => 'sportsbook_bg.jpg'
            ]);
        }

        $isPageSettingExists = $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID())
            ->where('name', '=', 'is_sportsbook')
            ->where('value', '=', 1)
            ->exists();

        if (!$isPageSettingExists) {
            $this->connection->table($this->tablePageSettings)->insert([
                'page_id' => $this->getPageID(),
                'name' => 'is_sportsbook',
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
            ->where('alias', '=', 'sportsbook-prematch')
            ->where('name', '=', '#menu.secondary.sportsbook')
            ->where('check_permission', '=', 0)
            ->exists();

        if (!$isMenuExists) {
            $this->connection->table($this->tableMenus)->insert([
                'parent_id' => $this->getParentMenuID(),
                'alias' => 'sportsbook-prematch',
                'name' => '#menu.secondary.sportsbook',
                'priority' => 393,
                'link_page_id' => $this->getPageID(),
                'link' => '',
                'getvariables' => '',
                'included_countries' => '',
                'excluded_countries' => 'ES IT DE DK NL SE',
                'icon' => 'icon-vs-sportsbook',
                'check_permission' => 0
            ]);
        }
    }

    private function getPageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'sports')
            ->where('cached_path', '=', '/sports')
            ->first();

        return (int)$page->page_id;
    }

    private function getPageID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/sports/prematch')
            ->first();

        return (int)$page->page_id;
    }

    private function getParentMenuID(): int
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'secondary-top-menu')
            ->first();

        return (int)$menu->menu_id;
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (!$this->shouldRunSeeder) {
            return false;
        }

        $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'sportsbook-prematch')
            ->where('name', '=', '#menu.secondary.sportsbook')
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
            ->where('name', '=', 'landing_bkg')
            ->delete();

        $this->connection
            ->table($this->tablePageSettings)
            ->where('page_id', '=', $this->getPageID())
            ->where('name', '=', 'is_sportsbook')
            ->where('value', '=', 1)
            ->delete();

        $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'prematch')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/sports/prematch')
            ->delete();
    }
}