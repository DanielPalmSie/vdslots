<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddBoxesForSubMenusInBoxesTables extends Migration
{
    private string $pages_table = 'pages';
    private string $boxes_table = 'boxes';
    private string $boxes_attributes_table = 'boxes_attributes';
    private Connection $connection;

    private array $desktop_sub_menu_boxes = [
        [
            'box_class' => 'ContainerBox',
            'priority' => 0,
        ],
        [
            'box_class' => 'JsBannerRotatorBox',
            'priority' => 1,
        ],
        [
            'box_class' => 'ContainerBox',
            'priority' => 2,
        ],
        [
            'box_class' => 'DynamicImageBox',
            'priority' => 3,
        ],
        [
            'box_class' => 'DynamicImageBox',
            'priority' => 4,
        ],
        [
            'box_class' => 'MgGameChooseBox',
            'priority' => 5,
        ],
        [
            'box_class' => 'SimpleExpandableBox',
            'priority' => 6,
        ],
    ];

    private array $mobile_sub_menu_boxes = [
        [
            'box_class' => 'MobileStartBox',
            'priority' => 0,
        ],
        [
            'box_class' => 'MgMobileGameChooseBox',
            'priority' => 1,
        ]
    ];

    private array $cached_paths_pages = [
        '/jackpots',
        '/live-casino',
        '/mobile/jackpots',
        '/mobile/live-casino'
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (getenv('APP_SHORT_NAME') === 'VS') {
            return;
        }
        $this->createBoxesForSubMenu();
        $this->addBoxAttributesForLiveCasino();
        $this->addBoxAttributesForJackpot();
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') === 'VS') {
            return;
        }
        $this->deleteBoxesForSubMenu();
        $this->deleteBoxAttributesForLiveCasinoAndJackpot();
    }

    private function createBoxesForSubMenu()
    {
        $array_page_id_cached_path = $this->getArrayOfPageIDAndCachedPath($this->cached_paths_pages);

        foreach($array_page_id_cached_path as $arr) {
            if($arr['cached_path'] === '/jackpots' || $arr['cached_path'] === '/live-casino' ) {
                foreach($this->desktop_sub_menu_boxes as $box) {
                    $box['page_id'] = $arr['page_id'];
                    $box['container'] = 'full';

                    $this->connection->table($this->boxes_table)->insert($box);
                }
                continue;
            }

            foreach($this->mobile_sub_menu_boxes as $box) {
                $box['page_id'] = $arr['page_id'];
                $box['container'] = 'full';

                $this->connection->table($this->boxes_table)->insert($box);
            }

        }
    }

    private function getArrayOfPageIDAndCachedPath($cached_paths_pages): array
    {
        $page_id_cached_path_array = [];
        foreach($cached_paths_pages as $cached_path) {
            $page = $this->connection->table($this->pages_table)
                ->where('cached_path', '=', $cached_path)
                ->first();
            $page_id_cached_path_array[] = ['page_id' => $page->page_id, 'cached_path' => $page->cached_path];
        }

        return $page_id_cached_path_array;
    }

    private function deleteBoxesForSubMenu()
    {
        $array_page_id_cached_path = $this->getArrayOfPageIDAndCachedPath($this->cached_paths_pages);
        foreach($array_page_id_cached_path as $arr) {
                $this->connection->table($this->boxes_table)
                    ->where('page_id', '=', $arr['page_id'])
                    ->delete();
        }
    }

    private function getBoxID($box_class, $cached_path): int
    {
        $box = $this->connection->table($this->pages_table)
            ->join($this->boxes_table, 'pages.page_id', '=', 'boxes.page_id')
            ->where('boxes.box_class', '=', $box_class)
            ->where('cached_path', '=', $cached_path)
            ->first();

        return (int) $box->box_id;
    }

    private function addBoxAttributesForLiveCasino()
    {
        $this->connection->table($this->boxes_attributes_table)
            ->insert(
                    [
                        'box_id' => $this->getBoxID('MgMobileGameChooseBox', '/mobile/live-casino'),
                        'attribute_name' => 'tags',
                        'attribute_value' => 'live-casino'
                    ]
            );

        $this->connection->table($this->boxes_attributes_table)
            ->insert(
                [
                    'box_id' => $this->getBoxID('MgGameChooseBox', '/live-casino'),
                    'attribute_name' => 'tag',
                    'attribute_value' => 'live-casino'
                ]
            );
    }

    private function addBoxAttributesForJackpot()
    {
        $this->connection->table($this->boxes_attributes_table)
            ->insert(
                [
                    'box_id' => $this->getBoxID('MgMobileGameChooseBox', '/mobile/jackpots'),
                    'attribute_name' => 'tags',
                    'attribute_value' => 'videoslots,videoslots-nobonus,videoslots_jackpot,videoslots_jackpotbsg,videoslots_jackpotsheriff'
                ]
            );

        $this->connection->table($this->boxes_attributes_table)
            ->insert(
                [
                    'box_id' => $this->getBoxID('MgGameChooseBox', '/jackpots'),
                    'attribute_name' => 'tag',
                    'attribute_value' => 'videoslots,videoslots-nobonus,videoslots_jackpot,videoslots_jackpotbsg,videoslots_jackpotsheriff'
                ]
            );
    }

    private function deleteBoxAttributesForLiveCasinoAndJackpot()
    {
        $this->connection->table($this->boxes_attributes_table)
            ->where('box_id', '=', $this->getBoxID('MgMobileGameChooseBox', '/mobile/live-casino'))
            ->delete();

        $this->connection->table($this->boxes_attributes_table)
            ->where('box_id', '=', $this->getBoxID('MgGameChooseBox', '/live-casino'))
            ->delete();

        $this->connection->table($this->boxes_attributes_table)
            ->where('box_id', '=', $this->getBoxID('MgMobileGameChooseBox', '/mobile/jackpots'))
            ->delete();

        $this->connection->table($this->boxes_attributes_table)
            ->where('box_id', '=', $this->getBoxID('MgGameChooseBox', '/jackpots'))
            ->delete();
    }
}
