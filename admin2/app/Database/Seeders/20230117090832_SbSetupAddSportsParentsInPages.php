<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Helpers\SportsbookHelper;

class SbSetupAddSportsParentsInPages extends Seeder
{

    private string $tablePages;
    private Connection $connection;
    private bool $shouldRunSeeder;

    public function init()
    {
        $this->tablePages = 'pages';
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

        //Parent No 1
        $isPageExists = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'sports')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/sports')
            ->first();

        if (!$isPageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => 0,
                'alias' => 'sports',
                'filename' => 'diamondbet/generic.php',
                'cached_path' => '/sports',
            ]);
        }

        //Parent No 2
        $isPageExists = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'sports')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/sports')
            ->first();

        if (!$isPageExists) {
            $this->connection->table($this->tablePages)->insert([
                'parent_id' => $this->getPageParentID(),
                'alias' => 'sports',
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile/sports',
            ]);
        }
    }

    private function getPageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'mobile')
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int)$page->page_id;
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (!$this->shouldRunSeeder) {
            return false;
        }

        //Parent No 1
        $page = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', 0)
            ->where('alias', '=', 'sports')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', '/sports');
        $this->doPageDelete($page);


        //Parent No 2
        $page = $this->connection
            ->table($this->tablePages)
            ->where('parent_id', '=', $this->getPageParentID())
            ->where('alias', '=', 'sports')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile/sports');
        $this->doPageDelete($page);

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