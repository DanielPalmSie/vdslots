<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG72Config extends Seeder
{
    private array $configs;

    public function init()
    {
        $this->configs = [
            [
                "config_name" => 'RG72-net-deposit',
                "config_tag" => 'RG',
                "config_value" => "100000:GB",
                "config_type" => json_encode([
                    "type" => "ISO2-template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Amount><delimiter><:ISO2>"
                ], JSON_THROW_ON_ERROR)
            ],
            [
                "config_name" => 'RG72-hours',
                "config_tag" => 'RG',
                "config_value" => 24,
                "config_type" => json_encode(["type"=>"number"]),
            ]
        ];
    }

    public function up()
    {
        foreach ($this->configs as $config) {
            Config::create($config);
        }
    }

    public function down()
    {
        foreach ($this->configs as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}