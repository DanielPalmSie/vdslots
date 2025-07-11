<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddWbaPagesForUkOnMrvegas extends Seeder
{
    protected string $tablePages;
    protected string $tableBoxes;
    protected string $tablePageSettings;

    private Connection $connection;

    private array $cachedPaths;
    private array $wbaAliases;

    private array $pageData;
    private array $boxData;
    private array $pageSettingsData;


    public function init()
    {
        // Initialization
        $this->tablePages = 'pages';
        $this->tableBoxes = 'boxes';
        $this->tablePageSettings = 'page_settings';

        $this->connection = DB::getMasterConnection();

        $this->cachedPaths['wba2'] = '/wba2';
        $this->wbaAliases['wba2'] = 'wba2';

        $this->cachedPaths['wba'] = '/wba';
        $this->wbaAliases['wba'] = 'wba';

        $this->pageData = [
            [
                'parent_id' => 0,
                'alias' => $this->wbaAliases['wba'],
                'filename' => 'diamondbet/generic.php',
                'cached_path' => $this->cachedPaths['wba'],
            ],
            [
                'parent_id' => $this->getMobilePageParentID(),
                'alias' => $this->wbaAliases['wba'],
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile' . $this->cachedPaths['wba'],
            ],
            [
                'parent_id' => 0,
                'alias' => $this->wbaAliases['wba2'],
                'filename' => 'diamondbet/generic.php',
                'cached_path' => $this->cachedPaths['wba2'],
            ],
            [
                'parent_id' => $this->getMobilePageParentID(),
                'alias' => $this->wbaAliases['wba2'],
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile' . $this->cachedPaths['wba2'],
            ],
        ];

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
        | Add Page record
        |--------------------------------------------------------------------------
        */

        foreach ($this->pageData as $data) {
            $isPageExists = $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $data['parent_id'])
                ->where('alias', '=', $data['alias'])
                ->where('filename', '=', $data['filename'])
                ->where('cached_path', '=', $data['cached_path'])
                ->exists();

            if (!$isPageExists) {
                $this->connection->table($this->tablePages)->insert($data);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Update page_settings for Page record
        |--------------------------------------------------------------------------
        */

        $this->pageSettingsData = [
            [
                'page_id' => $this->getPageID($this->cachedPaths['wba'], $this->wbaAliases['wba']),
                'name' => 'landing_bkg',
            ],
            [
                'page_id' => $this->getPageID($this->cachedPaths['wba2'], $this->wbaAliases['wba2']),
                'name' => 'landing_bkg',
            ],

        ];

        foreach ($this->pageSettingsData as $data) {
            $isPageSettingExists = $this->connection
                ->table($this->tablePageSettings)
                ->where('page_id', '=', $data['page_id'])
                ->where('name', '=', $data['name'])
                ->exists();

            if (!$isPageSettingExists) {
                $this->connection->table($this->tablePageSettings)->insert([
                    'page_id' => $data['page_id'],
                    'name' => $data['name'],
                    'value' => 'MV-BG.jpg'
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Update Boxes for PageId
        |--------------------------------------------------------------------------
        */

        $this->boxData = [
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'page_id' => $this->getPageID($this->cachedPaths['wba'], $this->wbaAliases['wba']),
            ],
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'page_id' => $this->getPageID('/mobile' . $this->cachedPaths['wba'], $this->wbaAliases['wba']),
            ],
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'page_id' => $this->getPageID($this->cachedPaths['wba2'], $this->wbaAliases['wba2']),
            ],
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'page_id' => $this->getPageID('/mobile' . $this->cachedPaths['wba2'], $this->wbaAliases['wba2']),
            ],
        ];

        foreach ($this->boxData as $data) {
            $isBoxExists = $this->connection
                ->table($this->tableBoxes)
                ->where('container', '=', $data['container'])
                ->where('box_class', '=', $data['box_class'])
                ->where('page_id', '=', $data['page_id'])
                ->exists();

            if (!$isBoxExists) {
                $this->connection->table($this->tableBoxes)->insert(array_merge($data, ['priority' => 0]));
            }
        }

    }

    /**
     * Undo the migration
     */

    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Boxes for PageId
        |--------------------------------------------------------------------------
        */

        $this->boxData = [
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'priority' => 0,
                'page_id' => $this->getPageID($this->cachedPaths['wba'], $this->wbaAliases['wba']),
            ],
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'priority' => 0,
                'page_id' => $this->getPageID('/mobile' . $this->cachedPaths['wba'], $this->wbaAliases['wba']),
            ],
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'priority' => 0,
                'page_id' => $this->getPageID($this->cachedPaths['wba2'], $this->wbaAliases['wba2']),
            ],
            [
                'container' => 'full',
                'box_class' => 'PromotionPartnershipBox',
                'priority' => 0,
                'page_id' => $this->getPageID('/mobile' . $this->cachedPaths['wba2'], $this->wbaAliases['wba2']),
            ],
        ];

        foreach ($this->boxData as $data) {
            $this->connection
                ->table($this->tableBoxes)
                ->where('container', '=', $data['container'])
                ->where('box_class', '=', $data['box_class'])
                ->where('priority', '=', $data['priority'])
                ->where('page_id', '=', $data['page_id'])
                ->delete();
        }

        /*
       |--------------------------------------------------------------------------
       | Delete Page setting records
       |--------------------------------------------------------------------------
       */

        $this->pageSettingsData = [
            [
                'page_id' => $this->getPageID($this->cachedPaths['wba'], $this->wbaAliases['wba']),
                'name' => 'landing_bkg',
                'value' => 'MV-BG.jpg'
            ],
            [
                'page_id' => $this->getPageID($this->cachedPaths['wba2'], $this->wbaAliases['wba2']),
                'name' => 'landing_bkg',
                'value' => 'MV-BG.jpg'
            ],

        ];

        foreach ($this->pageSettingsData as $data) {
            $isPageSettingExists = $this->connection
                ->table($this->tablePageSettings)
                ->where('page_id', '=', $data['page_id'])
                ->where('name', '=', $data['name'])
                ->where('value', '=', $data['value'])
                ->first();

            if ($isPageSettingExists) {
                $this->connection
                    ->table($this->tablePageSettings)
                    ->where('page_id', '=', $data['page_id'])
                    ->where('name', '=', $data['name'])
                    ->where('value', '=', $data['value'])
                    ->delete();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Delete Page records
        |--------------------------------------------------------------------------
       */

        foreach ($this->pageData as $data) {
            $isPageExists = $this->connection->table($this->tablePages)
                ->where('alias', '=', $data['alias'])
                ->where('parent_id', '=', $data['parent_id'])
                ->where('cached_path', '=', $data['cached_path'])
                ->first();

            if ($isPageExists) {
                $this->connection
                    ->table($this->tablePages)
                    ->where('parent_id', '=', $data['parent_id'])
                    ->where('alias', '=', $data['alias'])
                    ->where('filename', '=', $data['filename'])
                    ->where('cached_path', '=', $data['cached_path'])
                    ->delete();
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