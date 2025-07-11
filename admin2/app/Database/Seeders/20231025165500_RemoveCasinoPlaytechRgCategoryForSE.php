<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveCasinoPlaytechRgCategoryForSE extends Seeder
{

    private Connection $connection;
    private string $excluded_country;
    private string $brand;
    private string $table;

    public function init()
    {
        $this->table = 'menus';
        $this->excluded_country = 'SE';
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        if ($this->brand === 'mrvegas') {
            $query = $this->connection
                ->table($this->table)
                ->where('alias', 'casino-playtech');

            $current_value = $query->get('excluded_countries');
            $current_value = empty($current_value) ? '' : $current_value->first()->excluded_countries;
            $excluded_countries = $current_value . ' ' . $this->excluded_country;

            $query->update(['excluded_countries' => $excluded_countries]);
        }
    }

    public function down()
    {
        if ($this->brand === 'mrvegas') {
            $query = $this->connection
                ->table($this->table)
                ->where('alias', 'casino-playtech');

            $current_value = $query->get('excluded_countries');
            $current_value = empty($current_value) ? '' : $current_value->first()->excluded_countries;
            $excluded_countries = str_replace(' ' . $this->excluded_country, "", $current_value);

            $query->update(['excluded_countries' => $excluded_countries]);
        }
    }
}
