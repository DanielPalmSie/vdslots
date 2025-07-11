<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateNetDepositLimitMonthJurisdictionsConfigValue extends Seeder
{
    private string $config_name;

    public function init()
    {
        $this->config_name = 'net-deposit-limit-month-jurisdictions';
    }

    public function up()
    {
        Config::where('config_name', $this->config_name)->update(["config_value" => 'UKGC,SGA,DGA,DGOJ,ADM,AGCO,MGA']);
    }

    public function down()
    {
        Config::where('config_name', $this->config_name)->update(["config_value" => 'UKGC,SGA,DGA,DGOJ,ADM,AGCO']);
    }
}