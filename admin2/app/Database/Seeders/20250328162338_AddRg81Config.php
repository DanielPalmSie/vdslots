<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRg81Config extends Seeder
{

    public function init()
    {
        $trigger = 'RG81';
        $config_tag = 'RG';

        $this->configs = [
            [
                "config_name" => "{$trigger}-top-unique-bets-customers",
                "config_tag" => $config_tag,
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Jurisdiction><delimiter><:Top_unique_bets_customers>"
                ], JSON_THROW_ON_ERROR),
            ],
            [
                "config_name" => "{$trigger}-days",
                "config_tag" => $config_tag,
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Jurisdiction><delimiter><:Days>"
                ], JSON_THROW_ON_ERROR),
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
