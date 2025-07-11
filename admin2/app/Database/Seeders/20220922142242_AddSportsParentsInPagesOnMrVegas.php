<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddSportsParentsInPagesOnMrVegas extends Seeder
{
    private string $tablePages;
    private Connection $connection;

    public function init()
    {
        $this->tablePages = 'pages';
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
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
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
