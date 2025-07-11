<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class RG82Config extends Seeder
{
    private array $configs;

    public function init()
    {
        $trigger = 'RG82';
        $config_tag = 'RG';
        $config_type_top = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Top_time_spent_customers>"
        ], JSON_THROW_ON_ERROR);

        $config_type_days = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Days>"
        ], JSON_THROW_ON_ERROR);

        $this->configs = [
            [
                "config_name" => "{$trigger}-top-time-spent-customers",
                "config_tag" => $config_tag,
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => $config_type_top,
            ],
            [
                "config_name" => "{$trigger}-days",
                "config_tag" => $config_tag,
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => $config_type_days,
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
