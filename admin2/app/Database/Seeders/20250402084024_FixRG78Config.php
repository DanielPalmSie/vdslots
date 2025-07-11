<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class FixRG78Config extends Seeder
{
    private array $configs;

    /**
     * @throws JsonException
     */
    public function init()
    {
        $this->configs = [
            [
                "config_name" => 'RG78-losing-customers',
                "config_tag" => 'RG',
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Jurisdiction><delimiter><:Losing_customers>"
                ], JSON_THROW_ON_ERROR)
            ],
            [
                "config_name" => 'RG78-months',
                "config_tag" => 'RG',
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Jurisdiction><delimiter><:Months>"
                ], JSON_THROW_ON_ERROR)
            ]
        ];
    }

    public function up()
    {
        foreach ($this->configs as $config) {
            Config::updateOrCreate(
                [
                    'config_name' => $config['config_name'],
                    'config_tag' => $config['config_tag']
                ],
                [
                    'config_value' => $config['config_value'],
                    'config_type' => $config['config_type']
                ]
            );
        }
    }

    public function down()
    {
        foreach ($this->configs as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}
