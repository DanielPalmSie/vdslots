<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddConfigsToSwitchOnOfRgEvaluationGlobally extends Seeder
{

    private string $configName;

    public function init()
    {
        $this->configName = 'rg-evaluation-state';
    }

    public function up()
    {
        Config::create([
            'config_name' => $this->configName,
            'config_tag' => 'RG',
            'config_type' => '{"type":"choice","values":["on","off"]}',
            'config_value' => 'off'
        ]);
    }

    public function down()
    {
        Config::where('config_name', $this->configName)->delete();
    }
}