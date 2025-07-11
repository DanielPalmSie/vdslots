<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class InsertIntoConfigCountriesWithCertifiedGames1 extends Seeder
{

    public function up()
    {
        $config = [
            'config_name' => 'countries',
            'config_tag' => 'countries_with_certified_games1',
            'config_value' => 'se,dk',
            'config_type' => '{"type":"iso2","next_data_delimiter":","}',
        ];
        Config::shs()->insert($config);
    }
}