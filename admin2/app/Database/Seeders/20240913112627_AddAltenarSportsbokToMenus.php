<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddAltenarSportsbokToMenus extends Seeder
{
    /** @var string */
    protected $tablePages;
    protected $tableMenus;
    protected $tableBoxes;
    protected $tablePageSettings;
    protected $tableStartGo;

    /** @var Connection */
    protected $connection;

    public function init(): void
    {
        $this->tablePages = 'pages';
        $this->tableMenus = 'menus';
        $this->tableBoxes = 'boxes';
        $this->tablePageSettings = 'page_settings';
        $this->tableStartGo = 'start_go';
        $this->connection = DB::getMasterConnection();
    }

    public function up(): void
    {
        /** Standard page */
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => 0,
            'alias' => 'sportsbook',
            'filename' => 'diamondbet/generic.php',
            'cached_path' => '/sportsbook',
        ]);

        /** Mobile page */
        $this->connection->table($this->tablePages)->insert([
            'parent_id' => $this->getMobileRootPageId(),
            'alias' => 'sportsbook',
            'filename' => 'diamondbet/mobile.php',
            'cached_path' => '/mobile/sportsbook',
        ]);

        /** Standard page box */
        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'AltenarBox',
            'priority' => 0,
            'page_id' => $this->getStandardPageID(),
        ]);

        /** Mobile page box */
        $this->connection->table($this->tableBoxes)->insert([
            'container' => 'full',
            'box_class' => 'AltenarBox',
            'priority' => 0,
            'page_id' => $this->getMobilePageID(),
        ]);

        /** Standard page menu */
        $this->connection->table($this->tableMenus)->insert([
            'parent_id' => $this->getParentStandardMenuID(),
            'alias' => 'altenar',
            'name' => '#menu.secondary.altenar',
            'priority' => 409,
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
            'alias' => 'mobile-secondary-menu-altenar',
            'name' => 'menu.secondary.altenar',
            'priority' => 410,
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
            'from' => '/sportsbook',
            'to' => '/mobile/sportsbook',
        ]);

        /** Background image - standard page */
        $this->connection->table('page_settings')->insert([
            'page_id' => $this->getStandardPageID(),
            'name' => 'landing_bkg',
            'value' => 'DbetBackground_bg.jpg',
        ]);

        /** Background image - mobile page */
        $this->connection->table('page_settings')->insert([
            'page_id' => $this->getMobilePageID(),
            'name' => 'landing_bkg',
            'value' => 'DbetBackground_bg.jpg',
        ]);
    }

    public function down(): void
    {
        /** Delete standard page */
        $this->connection->table($this->tablePages)
            ->where('cached_path', '=', '/sportsbook')
            ->delete();

        /** Delete mobile page */
        $this->connection->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/sportsbook')
            ->delete();

        /** Delete page boxes */
        $this->connection->table($this->tableBoxes)
            ->where('box_class', '=', 'AltenarBox')
            ->delete();

        /** Delete standard page menu */
        $this->connection->table($this->tableMenus)
            ->where('alias', '=', 'altenar')
            ->delete();

        /** Delete mobile page menu */
        $this->connection->table($this->tableMenus)
            ->where('alias', '=', 'mobile-secondary-menu-altenar')
            ->delete();

        /** Delete redirects */
        $this->connection->table($this->tableStartGo)
            ->where('from', '=', '/sportsbook')
            ->delete();

        /** Delete standard page background */
        $this->connection->table('page_settings')
            ->where('name', '=', 'landing_bkg')
            ->where('page_id', '=', $this->getStandardPageID())
            ->delete();

        /** Delete mobile page background */
        $this->connection->table('page_settings')
            ->where('name', '=', 'landing_bkg')
            ->where('page_id', '=', $this->getMobilePageID())
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

    private function getMobilePageID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/mobile/sportsbook')
            ->first();

        return (int)$page->page_id;
    }

    private function getStandardPageID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', '/sportsbook')
            ->first();

        return (int)$page->page_id;
    }

    private function getParentStandardMenuID(): int
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'secondary-top-menu')
            ->first();

        return (int)$menu->menu_id;
    }

    private function getParentMobileMenuID(): int
    {
        $menu = $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'mobile-secondary-top-menu')
            ->first();

        return (int)$menu->menu_id;
    }
}