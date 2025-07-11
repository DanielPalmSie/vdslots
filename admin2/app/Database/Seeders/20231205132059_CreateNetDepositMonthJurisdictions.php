<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class CreateNetDepositMonthJurisdictions extends Seeder
{
    private array $config;

    public function init()
    {
        $this->config = [
            "config_name" => 'net-deposit-limit-month-jurisdictions',
            "config_tag" => 'net-deposit-limit',
            "config_value" => 'UKGC,SGA,DGA,DGOJ,ADM,AGCO',
            "config_type" => json_encode([
                "type" => "template",
                "next_data_delimiter" => ",",
                "format" => "<:Jurisdictions><delimiter>"
            ]),
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
