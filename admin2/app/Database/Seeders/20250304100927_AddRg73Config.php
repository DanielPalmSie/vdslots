<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRg73Config extends Seeder
{
    private array $configs;

    public function init()
    {
        $trigger = 'RG73';
        $config_tag = 'RG';
        $config_type = json_encode([
            "type" => "template",
            "delimiter" => "::",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Hours>"
        ], JSON_THROW_ON_ERROR);
        $initial_value = 'UKGC::0;SGA::0;DGA::0;DGOJ::0;ADM::0;AGCO::0;MGA::0';

        $this->configs = [
            [
                "config_name" => "{$trigger}-hours-played",
                "config_tag" => $config_tag,
                "config_value" => $initial_value,
                "config_type" => $config_type,
            ],
            [
                "config_name" => "{$trigger}-duration",
                "config_tag" => $config_tag,
                "config_value" => $initial_value,
                "config_type" => $config_type,
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
