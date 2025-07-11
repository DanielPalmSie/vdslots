<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddPopupsInteractionButtonsConfig extends Seeder
{
    public function up()
    {
        $config = [
            "config_name" => 'popups-interaction-buttons',
            "config_tag" => 'RG',
            "config_value" => 'take-a-break:0,edit-limits:0,continue:0',
            "config_type" => json_encode([
                "type" => "template",
                "delimiter" => ":",
                "next_data_delimiter" => ",",
                "format" => "<:Action><delimiter><:Value>"
            ], JSON_THROW_ON_ERROR),
        ];

        Config::shs()->insert($config);
    }

    public function down()
    {
        Config::shs()->where("config_name", "popups-interaction-buttons")->delete();
    }
}