<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class UpdateBoxAttributesToFixPromotionalBanners extends Seeder
{
    protected string $tableBoxesAttributes;
    private Connection $connection;
    private array $boxesAttributesData;
    protected string $newAttributeValue;

    private $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->tableBoxesAttributes = 'boxes_attributes';
        $this->newAttributeValue = '';

        $this->boxesAttributesData = [

            [
                'box_id' => 858,
                'attribute_name' => 'auto_out_en',
                'attribute_value' => 'yes'
            ],
            [
                'box_id' => 858,
                'attribute_name' => 'auto_out_it',
                'attribute_value' => 'yes'
            ],
            [
                'box_id' => 858,
                'attribute_name' => 'auto_out_on',
                'attribute_value' => 'yes'
            ]
        ];
    }

    public function up()
    {
        $this->init();

        if ($this->brand !== 'videoslots') {
            return;
        }

        foreach ($this->boxesAttributesData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableBoxesAttributes)
                ->where('box_id', '=', $data['box_id'])
                ->where('attribute_value', '=', $data['attribute_value'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableBoxesAttributes)
                    ->where('box_id', '=', $data['box_id'])
                    ->where('attribute_name', '=', $data['attribute_name'])
                    ->where('attribute_value', '=', $data['attribute_value'])
                    ->update(['attribute_value' => $this->newAttributeValue]);
            }
        }

    }

    public function down()
    {

        $this->init();

        if ($this->brand !== 'videoslots') {
            return;
        }

        foreach ($this->boxesAttributesData as $data) {
            $isDataExists = $this->connection
                ->table($this->tableBoxesAttributes)
                ->where('box_id', '=', $data['box_id'])
                ->exists();

            if ($isDataExists) {
                $this->connection
                    ->table($this->tableBoxesAttributes)
                    ->where('box_id', '=', $data['box_id'])
                    ->where('attribute_name', '=', $data['attribute_name'])
                    ->where('attribute_value', '=', $this->newAttributeValue)
                    ->update(['attribute_value' => $data['attribute_value']]);
            }
        }
    }
}