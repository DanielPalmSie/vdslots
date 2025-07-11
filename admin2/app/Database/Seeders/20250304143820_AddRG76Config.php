<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG76Config extends Seeder
{
    private array $configs;

    /**
     * @throws JsonException
     */
    public function init()
    {
        $this->configs = [
            [
                "config_name" => 'RG76-multiplier',
                "config_tag" => 'RG',
                "config_value" => "UKGC:2500;SGA:2500;DGA:2500;DGOJ:2500;AGCO:2500;MGA:2500;",
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => ":",
                    "next_data_delimiter" => ";",
                    "format" => "<:Jurisdiction><delimiter><:Multiplier>"
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
