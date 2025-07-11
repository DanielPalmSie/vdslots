<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;
use App\Models\Trigger;

class CreateRgTriggerActionConfigs extends Seeder
{

    private array $triggers = [];

    public function init()
    {
        $this->triggers = Trigger::where('name', 'LIKE', "RG%")->get()->pluck('name')->toArray();
    }

    public function up()
    {
        foreach ($this->triggers as $trigger_name) {
            Config::create([
                "config_name" => "{$trigger_name}-trigger-action-days",
                "config_tag" => 'RG',
                "config_value" => 0,
                "config_type" => json_encode(["type" => "number"], JSON_THROW_ON_ERROR)
            ]);
            Config::create([
                "config_name" => "{$trigger_name}-trigger-action-count",
                "config_tag" => 'RG',
                "config_value" => 0,
                "config_type" => json_encode(["type" => "number"], JSON_THROW_ON_ERROR)
            ]);
            Config::create([
                'config_name' => "{$trigger_name}-trigger-action",
                'config_tag' => "RG",
                'config_type' => json_encode([
                    "type" => "choice",
                    "values" => [
                        'NoAction',
                        'TriggerManualReviewAction'
                    ],
                ], JSON_THROW_ON_ERROR),
                'config_value' => "NoAction",
            ]);
        }
    }

    public function down()
    {
        Config::where("config_name", "LIKE", "%-trigger-action%")
            ->where("config_tag", "RG")
            ->delete();
    }
}