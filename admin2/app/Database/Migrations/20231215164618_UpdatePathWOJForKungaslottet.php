<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdatePathWOJForKungaslottet extends Migration
{
    protected string $tablePages;

    private Connection $connection;

    private string $oldCachedPath;
    private string $newCachedPath;

    private string $oldAlias;
    private string $newAlias;

    private array $pageData;

    private $brand;

    public function init()
    {
        $this->tablePages = 'pages';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->oldAlias = 'the-wheel-of-vegas-info';
        //#   $this->oldAlias = 'the-wheel-of-jackpots-info'; ##local/test/pro vs
        $this->newAlias = 'kungahjulet-info';

        $this->oldCachedPath = '/the-wheel-of-vegas-info';
        //# $this->oldCachedPath = '/the-wheel-of-jackpots-info'; ##local/test/pro vs
        $this->newCachedPath = '/kungahjulet-info';

        $this->pageData = [
            [
                'parent_id' => 0,
                'alias' => $this->oldAlias,
                'filename' => 'diamondbet/generic.php',
                'cached_path' => $this->oldCachedPath,
                'new_alias' => $this->newAlias,
                'new_cached_path' => $this->newCachedPath
            ],
            [
                'parent_id' => $this->getMobilePageParentID(),
                'alias' => $this->oldAlias,
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile' . $this->oldCachedPath,
                'new_alias' => $this->newAlias,
                'new_cached_path' => '/mobile' . $this->newCachedPath,
            ],
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | Update new Page record
        |--------------------------------------------------------------------------
         */

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        foreach ($this->pageData as $data) {
            $isPageExists = $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $data['parent_id'])
                ->where('alias', '=', $data['alias'])
                ->where('filename', '=', $data['filename'])
                ->where('cached_path', '=', $data['cached_path'])
                ->exists();

            if ($isPageExists) {
                $this->connection
                    ->table($this->tablePages)
                    ->where('page_id', $this->getPageID($data['cached_path'], $data['alias']))
                    ->update([
                        'alias' => $data['new_alias'],
                        'cached_path' => $data['new_cached_path']
                    ]);
            }
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        /*
        |--------------------------------------------------------------------------
        | Update to previous Page record
        |--------------------------------------------------------------------------
         */

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        foreach ($this->pageData as $data) {
            $isPageExists = $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $data['parent_id'])
                ->where('alias', '=', $data['new_alias'])
                ->where('filename', '=', $data['filename'])
                ->where('cached_path', '=', $data['new_cached_path'])
                ->exists();

            if ($isPageExists) {
                $this->connection
                    ->table($this->tablePages)
                    ->where('page_id', $this->getPageID($data['new_cached_path'], $data['new_alias']))
                    ->update([
                        'alias' => $data['alias'],
                        'cached_path' => $data['cached_path'],
                    ]);
            }
        }

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

    private function getPageID(string $cache_path, string $alias): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', $alias)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }
}
