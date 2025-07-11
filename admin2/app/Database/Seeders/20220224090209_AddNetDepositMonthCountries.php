<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddNetDepositMonthCountries extends Seeder
{
    public function up()
    {
        $config = [
            "config_name" => 'net-deposit-limit-month-countries',
            "config_tag" => 'net-deposit-limit',
            "config_value" => 'GB,SE,DK,ES,IT',
            "config_type" => '{"type":"ISO2", "delimiter":" "}',
        ];

        Config::shs()->insert($config);
    }
}