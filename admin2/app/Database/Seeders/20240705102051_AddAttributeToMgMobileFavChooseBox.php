<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddAttributeToMgMobileFavChooseBox extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;
    protected array $boxesAttributesData;

    public function init()
    {
        $this->table = 'boxes_attributes';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->boxesAttributesData = [

            [
                'box_id' => 982,
                'attribute_name' => 'col_num_landscape_max',
                'attribute_value' => 4
            ],
            [
                'box_id' => 982,
                'attribute_name' => 'col_num_landscape_min',
                'attribute_value' => 4
            ],
            [
                'box_id' => 982,
                'attribute_name' => 'col_num_portrait_max',
                'attribute_value' => 3
            ],
            [
                'box_id' => 982,
                'attribute_name' => 'col_num_portrait_min',
                'attribute_value' => 3
            ]
        ];
    }

    public function up()
    {
        $this->init();

        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->boxesAttributesData as $data) {
            $this->connection
                ->table('boxes_attributes')
                ->insert([
                    'box_id' => $data['box_id'],
                    'attribute_name' => $data['attribute_name'],
                    'attribute_value' => $data['attribute_value'],
                ]);
        }
    }

    public function down()
    {
        $this->init();

        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->boxesAttributesData as $data) {
            $this->connection
                ->table('boxes_attributes')
                ->where('box_id', '=', $data['box_id'])
                ->where('attribute_name', '=', $data['attribute_name'])
                ->delete();
        }
    }
}
