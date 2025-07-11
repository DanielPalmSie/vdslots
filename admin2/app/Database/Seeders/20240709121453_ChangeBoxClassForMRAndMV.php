<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;


class ChangeBoxClassForMRAndMV extends Seeder
{
    private Connection $connection;

    private string $table;

    private string $brand;

    public function init()
    {
        $this->table = 'boxes_attributes';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if(!in_array($this->brand, ['mrvegas', 'megariches'])) {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where(['box_id' => 888])
            ->where(['attribute_name' => 'box_class'])
            ->update(['attribute_value' => 'boxes-container-transparent']);
    }

    public function down()
    {
        if(!in_array($this->brand, ['mrvegas', 'megariches'])) {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where(['box_id' => 888])
            ->where(['attribute_name' => 'box_class'])
            ->update(['attribute_value' => 'black']);
    }
}
