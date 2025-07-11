<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG80Config extends Seeder
{
    private array $configs;

    /**
     * @throws JsonException
     */
    public function init()
    {
        $this->configs = [
            [
                "config_name" => 'RG80-losing-young-customers',
                "config_tag" => 'RG',
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Jurisdiction><delimiter><:Losing_young_customers>"
                ], JSON_THROW_ON_ERROR)
            ],
            [
                "config_name" => 'RG80-months',
                "config_tag" => 'RG',
                "config_value" => "UKGC:0;SGA:0;DGA:0;DGOJ:0;AGCO:0;MGA:0",
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Jurisdiction><delimiter><:Months>"
                ], JSON_THROW_ON_ERROR)
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
