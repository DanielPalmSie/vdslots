<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRgEvaluationFlagsTriggersPercentageSetting extends Seeder
{

    public function init()
    {
        $this->config = [
            "config_name" => 'RG-evaluation-flags-triggers-percentage',
            "config_tag" => 'RG',
            "config_value" => "5:GB",
            "config_type" => json_encode([
                "type" => "ISO2-template",
                "delimiter" => ":",
                "next_data_delimiter" => ";",
                "format" => "<:Percentage><delimiter><:ISO2>"
            ], JSON_THROW_ON_ERROR)
        ];
    }

    public function up()
    {
        Config::create($this->config);
    }

    public function down()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }
}