<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddOverlayLinksForDynamicImageBoxMegariches extends Seeder
{
    private Connection $connection;
    private int $box_id;
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->box_id = 891;
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
            $this->connection->table('boxes_attributes')
                ->where('box_id', $this->box_id)
                ->where('attribute_name', 'LIKE', 'overlay_link%')
                ->update(['attribute_value' => '/welcome-bonus/']);
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            $this->connection->table('boxes_attributes')
                ->where('box_id', $this->box_id)
                ->where('attribute_name', 'LIKE', 'overlay_link%')
                ->update(['attribute_value' => '']);
        }
    }
}
