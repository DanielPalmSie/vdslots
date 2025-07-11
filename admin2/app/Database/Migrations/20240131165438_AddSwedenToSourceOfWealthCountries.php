<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddSwedenToSourceOfWealthCountries extends Migration
{
    private Connection $connection;
    private string $brand;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'config';
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection->table($this->table)
                ->where('config_name', 'source_of_wealth_countries')
                ->where('config_tag', 'documents')
                ->update(['config_value' => DB::raw('CONCAT(config_value, " SE")')]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection->table($this->table)
                ->where('config_name', 'source_of_wealth_countries')
                ->where('config_tag', 'documents')
                ->update(['config_value' => DB::raw('REPLACE(config_value, " SE", "")')]);
        }
    }
}
