<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;
use App\RgEvaluation\States\State;

class CreateRG6EvaluationLastStepActionStateConfig extends Seeder
{
    private string $dynamic_action_state_config_name = "RG6-evaluation-last-step-action-state";
    private string $configTag = 'RG';

    public function up()
    {
        Config::create([
            'config_name' => $this->dynamic_action_state_config_name,
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

    public function down()
    {
        Config::where('config_name', $this->dynamic_action_state_config_name)->delete();
    }
}