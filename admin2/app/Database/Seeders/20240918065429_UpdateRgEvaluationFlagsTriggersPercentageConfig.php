<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateRgEvaluationFlagsTriggersPercentageConfig extends Seeder
{
    private string $config_name;

    public function init()
    {
        $this->config_name = 'RG-evaluation-flags-triggers-percentage';
    }

    public function up()
    {
        Config::where('config_name', $this->config_name)
            ->where('config_tag', 'RG')
            ->update(['config_value' => '5:GB;5:SE;5:DK']);
    }

    public function down()
    {
        Config::where('config_name', $this->config_name)
            ->where('config_tag', 'RG')
            ->update(['config_value' => '5:GB;5:SE']);
    }
}