<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddConfigsToSwitchOnOfRgEvaluationTriggers extends Seeder
{

    /**
     * @var array|string[]
     */
    private array $rg_evaluation_triggers;

    public function init()
    {
        $this->rg_evaluation_triggers = [
            'RG6',
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
            'RG35',
            'RG38',
            'RG39',
            'RG59',
        ];
    }

    public function up()
    {
        foreach ($this->rg_evaluation_triggers as $trigger) {
            Config::create([
                "config_name" => "$trigger-evaluation-in-jurisdictions",
                "config_tag" => 'RG',
                "config_value" => '',
                "config_type" => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:Jurisdictions>"
                ], JSON_THROW_ON_ERROR)
            ]);
        }
    }

    public function down()
    {
        $config_list = array_map(function ($trigger) {
            return "$trigger-evaluation-in-jurisdictions";
        }, $this->rg_evaluation_triggers);
        Config::whereIn('config_name', $config_list)->delete();
    }
}