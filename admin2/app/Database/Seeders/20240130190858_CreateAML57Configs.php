<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class CreateAML57Configs extends Seeder
{
    /**
     * @var array|array[]
     */
    private array $configs;

    public function init()
    {
        $this->configs = [
            [
                "config_name" => 'AML57-psp',
                "config_tag" => 'AML',
                "config_value" => 'paysafe,flexepin,neosurf,cashtocode',
                "config_type" => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:Psp><delimiter>"
                ]),
            ],
            [
                "config_name" => 'AML57-duration-days',
                "config_tag" => 'AML',
                "config_value" => 30,
                "config_type" => '{"type":"number"}',
            ],
            [
                "config_name" => 'AML57-deposit-thold',
                "config_tag" => 'AML',
                "config_value" => 100000,
                "config_type" => '{"type":"number"}',
            ],
        ];
    }

    public function up()
    {
        foreach ($this->configs as $insert) {
            Config::create($insert);
        }
    }

    public function down()
    {
        foreach ($this->configs as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}