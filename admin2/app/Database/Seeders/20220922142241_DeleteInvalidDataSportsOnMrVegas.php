<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

/**
 * There are invalid pages with parent_id=0 on MrVegas Production DB, which are completely wrong and should be deleted.
 * usage: ./console seeder:up 20220922142241
 */
class DeleteInvalidDataSportsOnMrVegas extends Seeder
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

        // There are invalid pages with parent_id=0, which are completely wrong and should be deleted.
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
