<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class RemoveStringsunderlivecasinojackpot extends Migration
{

    protected $table;
    protected $pages_table;
    protected $boxes_table;

    protected $connection;
    private $brand;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->pages_table = 'pages';
        $this->boxes_table = 'boxes';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->pages_items = [
            [
                'cached_path' => '/mobile/live-casino',
                'box_class' => 'SimpleExpandableBox',
            ],
            [
                'cached_path' => '/mobile/jackpots',
                'box_class' => 'SimpleExpandableBox',
            ]
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand === 'megariches') {
            $this->connection
            ->table($this->table)
            ->whereIn('alias', ['simple.1591.html', 'simple.1603.html'])
            ->delete();

            foreach ($this->pages_items as $page_item) {
                $box_id = $this->getBoxID($page_item['box_class'], $page_item['cached_path']);
                $this->connection
                    ->table('boxes')
                    ->where('box_id', $box_id)
                    ->delete();
            }
        }
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

}
