<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use App\Helpers\SportsbookHelper;

class SbSetupDeleteInvalidSportsDataSetup extends Seeder
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

        // Remove invalid pages with parent_id=0.
        $pages = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', 0)
            ->whereIn('cached_path', ['/sports/live', '/sports/prematch', '/mobile/sports', '/mobile/sports/live'])
            ->get();

        foreach ($pages as $page) {
            $this->connection
                ->table($this->tableMenus)
                ->where('link_page_id', '=', $page->page_id)
                ->delete();

            $this->connection
                ->table($this->tableBoxes)
                ->where('page_id', '=', $page->page_id)
                ->delete();

            $this->connection
                ->table($this->tablePageSettings)
                ->where('page_id', '=', $page->page_id)
                ->delete();

            $this->connection
                ->table($this->tablePages)
                ->where('page_id', '=', $page->page_id)
                ->delete();
        }
    }
}