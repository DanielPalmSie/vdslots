<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddConfigMaximumLossLimitPerJurisdiction extends Seeder
{

    public function init()
    {
    }

    public function up()
    {
        $config = [
            "config_name" => 'responsible-gambling',
            "config_tag" => 'loss-limits',
            "config_value" => '',
            "config_type" => json_encode([
                "type" => "template",
                "delimiter" => "::",
                "next_data_delimiter" => "\n",
                "format" => "<:Jurisdiction><delimiter><:MaxLimit>"
            ], JSON_THROW_ON_ERROR),
        ];

        Config::shs()->insert($config);
    }

    public function down()
    {
        Config::shs()->where("config_tag", "loss-limits")->delete();
    }
}