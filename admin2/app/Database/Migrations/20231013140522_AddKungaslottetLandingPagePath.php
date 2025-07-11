<?php
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddKungaslottetLandingPagePath extends Migration
{
    protected string $tablePages;
    private string $tablePageSetting;
    protected string $tableBoxes;

    protected string $cachedPath;
    protected string $slotsAlias;
    private Connection $connection;
    /**
     * @var array[]
     */
    private array $pageData;
    /**
     * @var array[]
     */
    private array $pageSettingsData;
    /**
     * @var array[]
     */
    private array $boxData;


    public function init()
    {

        $this->tablePages = 'pages';
        $this->tablePageSetting = 'page_settings';
        $this->tableBoxes = 'boxes';

        $this->connection = DB::getMasterConnection();

        $this->cachedPath = '/landing';
        $this->slotsAlias = 'landing-page';

        $this->pageData = [
            [
                'parent_id' => 0,
                'alias' => $this->slotsAlias,
                'filename' => 'diamondbet/generic.php',
                'cached_path' => $this->cachedPath,
            ],
            [
                'parent_id' =>$this->getMobilePageParentID(),
                'alias' => $this->slotsAlias,
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile' . $this->cachedPath,
            ],
        ];

        $this->pageSettingsData = [
            '/landing' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_page',
                    'value' => 1,
                ],
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => 'Landing-page-Background.jpg',
                ],
                [
                    'page_id' => 0,
                    'name' => 'landing_logo',
                    'value' => 'Landing-page-logo.jpg',
                ],
            ],
            '/mobile/landing' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_page',
                    'value' => 1,
                ],
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => 'Landing-page-mobile-Background.jpg',
                ],
                [
                    'page_id' => 0,
                    'name' => 'landing_logo',
                    'value' => 'Landing-page-mobile-logo.jpg',
                ]
            ]
        ];

    }

    /**
     * Do the migration
     */
    public function up()
    {

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


            /*
            |--------------------------------------------------------------------------
            | Update page_settings for Page record
            | first create page then update page_settings
            |--------------------------------------------------------------------------
            */
            $page = $this->connection->table($this->tablePages)
                ->where('parent_id', '=', $data['parent_id'])
                ->where('alias', '=', $data['alias'])
                ->where('cached_path', '=', $data['cached_path'])
                ->first();

            if(!empty($page)) {
                $pageSettings = $this->pageSettingsData[$data['cached_path']];
                foreach ($pageSettings as $setting) {
                    $setting['page_id'] = $page->page_id;

                    $this->connection->table($this->tablePageSetting)
                        ->insert($setting);
                }
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
                'box_class' => 'HomeLandingPageBox',
                'page_id' => $this->getPageID($this->cachedPath),
            ],
            [
                'container' => 'full',
                'box_class' => 'HomeLandingPageBox',
                'page_id' => $this->getPageID('/mobile' . $this->cachedPath),
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
        /*
        |--------------------------------------------------------------------------
        | Delete Page records
        |--------------------------------------------------------------------------
        */
        foreach ($this->pageData as $data) {

            /*
           |--------------------------------------------------------------------------
           | Delete page_settings for Page record
           | first delete page_settings & then delete pages
           |--------------------------------------------------------------------------
           */
            $page = $this->connection->table($this->tablePages)
                ->where('alias', '=', $data['alias'])
                ->where('parent_id', '=', $data['parent_id'])
                ->where('cached_path', '=', $data['cached_path'])
                ->first();

            if(!empty($page)) {
                $pageSettings = $this->pageSettingsData[$data['cached_path']];
                foreach ($pageSettings as $setting) {
                    $this->connection->table($this->tablePageSetting)
                        ->where('name', '=', $setting['name'])
                        ->where('page_id', '=', $page->page_id)
                        ->delete();
                }
            }

            $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $data['parent_id'])
                ->where('alias', '=', $data['alias'])
                ->where('filename', '=', $data['filename'])
                ->where('cached_path', '=', $data['cached_path'])
                ->delete();
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Boxes for PageId
        |--------------------------------------------------------------------------
        */

        $this->boxData = [
            [
                'container' => 'full',
                'box_class' => 'HomeLandingPageBox',
                'page_id' => $this->getPageID($this->cachedPath),
            ],
            [
                'container' => 'full',
                'box_class' => 'HomeLandingPageBox',
                'page_id' => $this->getPageID('/mobile' . $this->cachedPath),
            ],
        ];

        foreach ($this->boxData as $data) {
            $this->connection
                ->table($this->tableBoxes)
                ->where('container', '=', $data['container'])
                ->where('box_class', '=', $data['box_class'])
                ->where('page_id', '=', $data['page_id'])
                ->delete();
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

    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }
}
