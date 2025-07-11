<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class DeleteNetDepositMonthCountries extends Seeder
{
    private array $config;

    public function init()
    {
        $this->config = [
            "config_name" => 'net-deposit-limit-month-countries',
            "config_tag" => 'net-deposit-limit',
            "config_value" => 'GB,SE,DK,ES,IT',
            "config_type" => json_encode([
                "type" => "ISO2",
                "delimiter" => ",",
            ]),
        ];
    }

    public function up()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }

    public function down()
    {
        Config::create($this->config);
    }
}
