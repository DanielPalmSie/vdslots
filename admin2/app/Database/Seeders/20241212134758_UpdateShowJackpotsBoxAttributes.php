<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;


class UpdateShowJackpotsBoxAttributes extends Seeder
{

    private string $pages_table = 'pages';
    private string $boxes_table = 'boxes';
    private string $boxes_attributes_table = 'boxes_attributes';
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();

    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {
            $this->updateAttributesForJackpot(32);
        }
    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->updateAttributesForJackpot();
        }
    }


    private function updateAttributesForJackpot($value = '') {
        $this->connection->table($this->boxes_attributes_table)
            ->upsert(
                [
                    'box_id' => $this->getBoxID('MgGameChooseBox', '/.'),
                    'attribute_name' => 'show_jackpots',
                    'attribute_value' => $value
                ],
                [
                 'box_id',
                 'attribute_name'
                ]
            );
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

}
