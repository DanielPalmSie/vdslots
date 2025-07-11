<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG5Config extends Seeder
{
    public function up()
    {
        Config::create([
            "config_name" => 'RG5-jurisdictions',
            "config_tag" => 'RG',
            "config_value" => 'DGOJ,SGA,UKGC,ADM',
            "config_type" => json_encode([
                "type" => "template",
                "next_data_delimiter" => ",",
                "format" => "<:Jurisdictions><delimiter>"
            ], JSON_THROW_ON_ERROR)
        ]);
    }

    public function down()
    {
        Config::where('config_name', 'RG5-jurisdictions')->delete();
    }
}