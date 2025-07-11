<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;
use App\Extensions\Database\FManager as DB;

class ExtendRG20ConfigsForSportsbook extends Seeder
{

    private array $configs;

    public function init()
    {
        $this->configs = [
            [
                'config_name' => 'RG20-sportsbook-brands',
                'config_tag' => 'RG',
                'config_value' => 'videoslots mrvegas',
                'config_type' => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => " ",
                    "format" => "<:Brand><delimiter>"
                ])
            ],
            [
                'config_name' => 'RG20-sportsbook-countries',
                'config_tag' => 'RG',
                'config_value' => 'GB SE MT',
                'config_type' => json_encode([
                    "type" => "ISO2",
                    "delimiter" => " ",
                ])
            ],
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
            $names[] = $config['config_name'];
        }
        Config::whereIn('config_name', $names)->delete();
    }
}
