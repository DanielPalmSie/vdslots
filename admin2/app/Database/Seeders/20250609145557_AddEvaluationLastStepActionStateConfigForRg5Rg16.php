<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;
use App\RgEvaluation\States\State;

class AddEvaluationLastStepActionStateConfigForRg5Rg16 extends Seeder
{
    private string $dynamic_action_state_config_name = "-evaluation-last-step-action-state";
    private array $triggers = [
        'RG5',
        'RG16'
    ];
    private string $configTag = 'RG';

    public function up()
    {
        foreach ($this->triggers as $trigger) {
            Config::create([
                'config_name' => $trigger . $this->dynamic_action_state_config_name,
                'config_tag' => $this->configTag,
                'config_type' => json_encode([
                    "type" => "choice",
                    "values" => [
                        State::NO_ACTION_STATE,
                        State::TRIGGER_MANUAL_REVIEW_STATE,
                    ]
                ], JSON_THROW_ON_ERROR),
                'config_value' => State::TRIGGER_MANUAL_REVIEW_STATE
            ]);
        }
    }

    public function down()
    {
        foreach ($this->triggers as $trigger) {
            Config::where('config_name', $trigger . $this->dynamic_action_state_config_name)->delete();
        }
    }
}