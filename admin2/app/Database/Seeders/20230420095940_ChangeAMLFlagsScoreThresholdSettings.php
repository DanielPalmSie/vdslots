<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class ChangeAMLFlagsScoreThresholdSettings extends Seeder
{
    /**
     * @var array
     */
    private array $config;
    /**
     * @var false|string
     */
    private string $template_type;
    /**
     * @var false|string
     */
    private string $number_type;

    public function init()
    {
        $this->template_type = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => " ",
            "format" => "<:Name><delimiter><:Number>"
        ]);
        $this->number_type = json_encode(["type" => "number"]);
        $this->config = [
            [
                'config_name' => 'aml-minimum-grs',
                'config' => [
                    'update' => ['value' => 80, 'type' => 'number_type'],
                    'revert' => ['value' => 80, 'type' => 'number_type']
                ]
            ],
            [
                'config_name' => 'AML17-score',
                'config' => [
                    'update' => ['value' => 'min:80 max:89', 'type' => 'template_type'],
                    'revert' => ['value' => 80, 'type' => 'number_type']
                ]
            ],
            [
                'config_name' => 'AML19-score',
                'config' => [
                    'update' => ['value' => 'min:90 max:99', 'type' => 'template_type'],
                    'revert' => ['value' => 90, 'type' => 'number_type']
                ]
            ],
            [
                'config_name' => 'AML23-score',
                'config' => [
                    'update' => ['value' => 'min:100 max:100', 'type' => 'template_type'],
                    'revert' => ['value' => 100, 'type' => 'number_type']
                ]
            ],
            [
                'config_name' => 'AML28-score',
                'config' => [
                    'update' => ['value' => 'min:100 max:100', 'type' => 'template_type'],
                    'revert' => ['value' => 100, 'type' => 'number_type']
                ]
            ],
            [
                'config_name' => 'AML43-score',
                'config' => [
                    'update' => ['value' => 'min:100 max:100', 'type' => 'template_type'],
                    'revert' => ['value' => 100, 'type' => 'number_type']
                ]
            ],
            [
                'config_name' => 'AML50',
                'config' => [
                    'update' => ['value' => 'min:80 max:80', 'type' => 'template_type'],
                    'revert' => ['value' => 80, 'type' => 'number_type']
                ]
            ],
        ];
    }

    public function up()
    {
        foreach ($this->config as $config) {
            Config::where('config_name', $config['config_name'])->update([
                'config_value' => $config['config']['update']['value'],
                'config_type' => $this->{$config['config']['update']['type']},
            ]);
        }
    }

    public function down()
    {
        foreach ($this->config as $config) {
            Config::where('config_name', $config['config_name'])->update([
                'config_value' => $config['config']['revert']['value'],
                'config_type' => $this->{$config['config']['revert']['type']},
            ]);
        }
    }
}