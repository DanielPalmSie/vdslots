<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateRgEvaluationFlagsTriggersPercentageSetting extends Seeder
{
    private string $config_name;

    public function init()
    {
        $this->config_name = 'RG-evaluation-flags-triggers-percentage';
    }

    public function up()
    {
        Config::where('config_name', $this->config_name)
            ->update(['config_value' => '5:GB;5:SE']);
    }

    public function down()
    {
        Config::where('config_name', $this->config_name)
            ->update(['config_value' => '5:GB']);
    }
}