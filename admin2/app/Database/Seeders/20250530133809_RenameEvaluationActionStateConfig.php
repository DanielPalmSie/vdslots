<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class RenameEvaluationActionStateConfig extends Seeder
{
    private string $old_config_name = "-evaluation-step-2-action-state";
    private $new_config_name = "-evaluation-last-step-action-state";
    public function up()
    {
        $this->updateConfigName($this->old_config_name, $this->new_config_name);
    }

    public function down()
    {
        $this->updateConfigName($this->new_config_name, $this->old_config_name);
    }

    private function updateConfigName(string $old_config_name, string $new_config_name)
    {
        $configs = Config::where('config_name', 'LIKE', "%{$old_config_name}")
            ->where('config_tag', 'RG')
            ->get();

        foreach ($configs as $config) {
            $name_parts = explode("-", $config['config_name']);
            $trigger_name = $name_parts[0];
            $config_name = $trigger_name . $new_config_name;
            Config::where('config_tag', 'RG')
                ->where('config_name', $config['config_name'])
                ->update(['config_name' => $config_name]);
        }
    }
}