<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;
use App\RgEvaluation\States\State;

class CreateRGXevaluationSelfAssessmentActionStateConfig extends Seeder
{
    /**
     * @var array|string[]
     */
    private array $rg_evaluation_triggers;
    private string $table;

    public function init()
    {
        $this->table = 'config';
        $this->rg_evaluation_triggers = [
            'RG8',
            'RG10',
            'RG11',
            'RG12',
            'RG13',
            'RG14',
            'RG19',
            'RG20',
            'RG21',
            'RG28',
            'RG29',
            'RG30',
            'RG31',
            'RG32',
            'RG33',
            'RG34',
            'RG38',
            'RG39',
            'RG59',
        ];
        $this->configName = '-evaluation-step-2-action-state';
        $this->configTag = 'RG';
    }

    public function up()
    {
        foreach ($this->rg_evaluation_triggers as $trigger) {
            $configData = [
                'config_name' => $trigger . $this->configName,
                'config_tag' => $this->configTag,
                'config_type' => json_encode([
                    "type" => "choice",
                    "values" => [
                        State::NO_ACTION_STATE,
                        State::TRIGGER_MANUAL_REVIEW_STATE,
                    ]
                ]),
                'config_value' => State::TRIGGER_MANUAL_REVIEW_STATE
            ];
            Config::create($configData);
        }
    }

    public function down()
    {
        Config::where('config_tag', '=', $this->configTag)
            ->where('config_name', 'LIKE', "%" . $this->configName . "%")
            ->delete();
    }
}