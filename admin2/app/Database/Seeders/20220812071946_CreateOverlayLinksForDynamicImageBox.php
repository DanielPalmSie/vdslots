<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class CreateOverlayLinksForDynamicImageBox extends Seeder
{
    /**
     * @var Connection
     */
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $languages = $this->connection->table('languages')->get();

        $this->connection
            ->table('boxes_attributes')
            ->join('boxes', 'boxes_attributes.box_id', '=', 'boxes.box_id')
            ->where('boxes.box_class', '=', 'DynamicImageBox')
            ->where('boxes_attributes.attribute_name', '=', 'overlay_link')
            ->where('boxes_attributes.attribute_value', '!=', '')
            ->get()
            ->map(function ($attribute) use($languages) {
                $data = [];
                foreach ($languages as $language) {
                    $data[] = [
                        'box_id' => $attribute->box_id,
                        'attribute_name' => "overlay_link_{$language->language}",
                        'attribute_value' => $attribute->attribute_value,
                    ];
                }
                $this->connection->table('boxes_attributes')->insert($data);
            });
    }

    public function down()
    {
        $this->connection->table('boxes_attributes')
            ->where('attribute_name', 'LIKE', 'overlay_link_%')
            ->delete();
    }
}