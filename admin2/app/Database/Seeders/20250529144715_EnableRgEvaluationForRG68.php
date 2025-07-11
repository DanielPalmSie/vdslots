<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;
use App\RgEvaluation\States\State;
class EnableRgEvaluationForRG68 extends Seeder
{
    private string $config_name = "RG68-evaluation-in-jurisdictions";
    private string $dynamic_action_state_config_name = "RG68-evaluation-last-step-action-state";
    private string $configTag = 'RG';

    public function up()
    {
        Config::create([
            "config_name" => $this->config_name,
            "config_tag" => 'RG',
            "config_value" => '',
            "config_type" => json_encode([
                "type" => "template",
                "next_data_delimiter" => ",",
                "format" => "<:Jurisdictions>"
            ], JSON_THROW_ON_ERROR)
        ]);

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
        Config::where('config_name', $this->config_name)->delete();
        Config::where('config_name', $this->dynamic_action_state_config_name)->delete();
    }
}
