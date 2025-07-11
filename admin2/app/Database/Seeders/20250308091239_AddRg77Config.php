
<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRg77Config extends Seeder
{
    private array $configs;

    public function init()
    {
        $trigger = 'RG77';
        $config_tag = 'RG';
        $config_type_top_depositors = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Top_Depositors>"
        ], JSON_THROW_ON_ERROR);

        $config_type_months = json_encode([
            "type" => "template",
            "delimiter" => ":",
            "next_data_delimiter" => ";",
            "format" => "<:Jurisdiction><delimiter><:Months>"
        ], JSON_THROW_ON_ERROR);

        $this->configs = [
            [
                "config_name" => "{$trigger}-top-depositors",
                "config_tag" => $config_tag,
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => $config_type_top_depositors,
            ],
            [
                "config_name" => "{$trigger}-months",
                "config_tag" => $config_tag,
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => $config_type_months,
            ]
        ];
    }

    public function up()
    {
        foreach ($this->configs as $config) {
            Config::create($config);
        }
    }

    public function down()
    {
        foreach ($this->configs as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}
