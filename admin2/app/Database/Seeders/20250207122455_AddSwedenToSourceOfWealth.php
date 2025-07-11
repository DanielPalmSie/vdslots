<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddSwedenToSourceOfWealth extends Migration
{
    private Connection $connection;
    private string $table;
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'config';
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if (in_array($this->brand, ['videoslots', 'mrvegas', 'kungaslottet', 'dbet'])) {
            $countryExists = $this->connection->table($this->table)
                ->where('config_name', 'source_of_wealth_countries')
                ->where('config_tag', 'documents')
                ->where('config_value', 'like', "%SE%")
                ->exists();

            if (!$countryExists) {
                $this->connection->table($this->table)
                    ->where('config_name', 'source_of_wealth_countries')
                    ->where('config_tag', 'documents')
                    ->update(['config_value' => DB::raw('CONCAT(config_value, " SE")')]);
            }
        }
    }

    public function down()
    {
        if (in_array($this->brand, ['videoslots', 'mrvegas', 'kungaslottet', 'dbet'])) {
            $countryExists = $this->connection->table($this->table)
                ->where('config_name', 'source_of_wealth_countries')
                ->where('config_tag', 'documents')
                ->where('config_value', 'like', "%SE%")
                ->exists();

            if ($countryExists) {
                $this->connection->table($this->table)
                    ->where('config_name', 'source_of_wealth_countries')
                    ->where('config_tag', 'documents')
                    ->update(['config_value' => DB::raw('REPLACE(config_value, " SE", "")')]);
            }
        }
    }
}
