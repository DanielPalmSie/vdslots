<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class WelcomeOfferActivationConfigurable extends Seeder
{

    /**
     * @var array|array[]
     */
    private array $config;

    public function init()
    {
        $this->config = [
            [
                "config_name" => 'show-welcome-offer-activation-popup',
                "config_tag" => 'activation-based-on-brand',
                "config_value" => 'yes',
                "config_type" => '{"type":"choice", "values":["yes","no"]}',
            ],
            [
                "config_name" => 'hide-welcome-offer-activation-popup-jurisdictions',
                "config_tag" => 'activation-based-on-jurisdictions',
                "config_value" => '',
                "config_type" => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:Jurisdictions><delimiter>"
                ]),
            ]
        ];
    }

    /**
     * Up the seeds
     */
    public function up()
    {
        foreach ($this->config as $config) {
            Config::create($config);
        }
    }

    /**
     * Undo the seeder
     */
    public function down()
    {
        foreach ($this->config as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }

}
