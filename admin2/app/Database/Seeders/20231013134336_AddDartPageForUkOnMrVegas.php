<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use App\Traits\WorksWithCountryListTrait;

class AddDartPageForUkOnMrVegas extends Seeder
{
    use WorksWithCountryListTrait;

    private string $pages_table;
    private string $boxes_table;

    private array $pages_items;
    private array $boxes_items;

    private string $menu_table;

    private array $pages_ids;
    private array $boxes_ids;

    protected string $cachedPath;
    protected string $dartsAlias;

    private Connection $connection;


    public function init()
    {
        $this->pages_table = 'pages';
        $this->boxes_table = 'boxes';

        $this->cachedPath = '/darts';
        $this->dartsAlias = 'darts';

        $this->menu_table = 'menus';

        $this->connection = DB::getMasterConnection();

        $this->pages_items = [
            'desktop' => [
                'parent_id' => $this->getDesktopPageParentID(),
                'alias' => $this->dartsAlias,
                'filename' => 'diamondbet/generic.php',
                'cached_path' => $this->cachedPath,
            ],
            'mobile' => [
                'parent_id' => $this->getMobilePageParentID(),
                'alias' => $this->dartsAlias,
                'filename' => 'diamondbet/mobile.php',
                'cached_path' => '/mobile' . $this->cachedPath,
            ]
        ];
        $this->boxes_items = [
            'desktop' => [
                [
                    'box_class' => 'FullImageBox',
                    'priority' => 0,
                    'container' => 'full',
                    'page_id' => $this->getPageID($this->cachedPath),
                ],
                [
                    'box_class' => 'SimpleExpandableBox',
                    'priority' => 1,
                    'container' => 'full',
                    'page_id' => $this->getPageID($this->cachedPath),
                ],
            ],
            'mobile' => [
                [
                    'box_class' => 'FullImageBox',
                    'priority' => 0,
                    'container' => 'full',
                    'page_id' => $this->getPageID('/mobile' . $this->cachedPath),
                ],
                [
                    'box_class' => 'SimpleExpandableBox',
                    'priority' => 1,
                    'container' => 'full',
                    'page_id' => $this->getPageID('/mobile' . $this->cachedPath),

                ]
            ]
        ];

        $this->pages_ids = ['desktop' => null, 'mobile' => null];
        $this->boxes_ids = ['desktop' => [], 'mobile' => []];

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

        foreach ($this->pages_items as $page_device => $page_item) {
            $page_exists = $this->connection
                ->table($this->pages_table)
                ->where('cached_path', $page_item['cached_path'])
                ->where('alias', $page_item['alias'])
                ->first();

            if (!empty($page_exists)) {
                $this->pages_ids[$page_device] = $page_exists->page_id;
            } else {
                $created_page = $this->connection
                    ->table($this->pages_table)
                    ->insert($page_item);

                if ($created_page) {
                    $page_exists = $this->connection
                        ->table($this->pages_table)
                        ->where('cached_path', $page_item['cached_path'])
                        ->where('alias', $page_item['alias'])
                        ->first();

                    $this->pages_ids[$page_device] = $page_exists->page_id;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Box record process
        |--------------------------------------------------------------------------
        */
        foreach ($this->boxes_items as $box_device => $box_items) {
            $page_id = $this->pages_ids[$box_device];

            foreach ($box_items as $box_item) {
                $box_exists = $this->connection
                    ->table($this->boxes_table)
                    ->where('page_id', $page_id)
                    ->where('box_class', $box_item['box_class'])
                    ->first();

                if (!empty($box_exists)) {
                    $this->boxes_ids[$box_device][] = $box_exists->box_id;
                } else {
                    $created_box = $this->connection
                        ->table($this->boxes_table)
                        ->insert([
                            'page_id' => $page_id,
                            'box_class' => $box_item['box_class'],
                            'priority' => $box_item['priority'],
                            'container' => $box_item['container']
                        ]);

                    if ($created_box) {
                        $box_exists = $this->connection
                            ->table($this->boxes_table)
                            ->where('page_id', $page_id)
                            ->where('box_class', $box_item['box_class'])
                            ->first();

                        $this->boxes_ids[$box_device][] = $box_exists->box_id;
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | menu record process
        |--------------------------------------------------------------------------
        */

        $isMenuExists = $this->connection
            ->table($this->menu_table)
            ->where('alias', '=', 'darts')
            ->where('name', '=', '#darts')
            ->where('check_permission', '=', 0)
            ->exists();

        if (!$isMenuExists) {
            $this->connection->table($this->menu_table)->insert([
                [
                    'parent_id' => $this->getDesktopPageParentID(),
                    'alias' => 'darts',
                    'name' => '#darts',
                    'priority' => 201,
                    'link_page_id' => $this->getPageID($this->cachedPath),
                    'link' => '',
                    'getvariables' => '',
                    'included_countries' => 'GB',
                    'excluded_countries' => '',
                    'excluded_provinces' => 'CA-ON',
                    'check_permission' => 0,
                    'logged_in' => 1,
                    'logged_out' => 1,
                    'icon' => ''
                ],
                [
                    'parent_id' => $this->getMobilePageParentID(),
                    'alias' => 'darts',
                    'name' => '#darts',
                    'priority' => 207,
                    'link_page_id' => $this->getPageID('/mobile' . $this->cachedPath),
                    'link' => '',
                    'getvariables' => '',
                    'included_countries' => 'GB',
                    'excluded_countries' => '',
                    'excluded_provinces' => 'CA-ON',
                    'logged_in' => 1,
                    'logged_out' => 1,
                    'check_permission' => 0,
                    'icon' => ''
                ]
            ]);
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
        | Delete Page records
        | Get pages, store ids in an array and delete
        |--------------------------------------------------------------------------
        */

        foreach ($this->pages_items as $page_device => $page_item) {
            $page_exists = $this->connection
                ->table($this->pages_table)
                ->where('cached_path', $page_item['cached_path'])
                ->where('alias', $page_item['alias'])
                ->first();

            if (!empty($page_exists)) {
                $this->pages_ids[$page_device] = $page_exists->page_id;
                $this->connection
                    ->table($this->pages_table)
                    ->where('page_id', $page_exists->page_id)
                    ->delete();
            }
        }

        /*
       |--------------------------------------------------------------------------
       | Delete Box records
       |--------------------------------------------------------------------------
       */

        foreach ($this->pages_ids as $page_id) {
            $this->connection
                ->table($this->boxes_table)
                ->where('page_id', $page_id)
                ->delete();
        }


        /*
       |--------------------------------------------------------------------------
       | Delete Menus records
       |--------------------------------------------------------------------------
       */


        foreach ($this->pages_ids as $page_id) {
            $menu_exists = $this->connection
                ->table($this->menu_table)
                ->where('alias', 'darts')
                ->where('name', '#darts')
                ->first();

            if (!empty($menu_exists)) {
                $this->connection
                    ->table($this->menu_table)
                    ->where('alias', 'darts')
                    ->where('name', '#darts')
                    ->where('link_page_id', $page_id)
                    ->delete();
            }
        }

    }


    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->pages_table)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }

    private function getBoxID($box_class, $cached_path): int
    {
        $box = $this->connection
            ->table($this->pages_table)
            ->join($this->boxes_table, 'pages.page_id', '=', 'boxes.page_id')
            ->where('boxes.box_class', '=', $box_class)
            ->where('cached_path', '=', $cached_path)
            ->first();

        return (int)$box->box_id;
    }

    private function getMobilePageParentID(): int
    {
        $page = $this->connection
            ->table($this->pages_table)
            ->where('alias', '=', 'mobile')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int)$page->page_id;
    }

    private function getDesktopPageParentID(): int
    {
        $page = $this->connection
            ->table($this->pages_table)
            ->where('alias', '=', '.')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->where('cached_path', '=', $this->cachedPath)
            ->first();

        return (int)$page->page_id;
    }

}