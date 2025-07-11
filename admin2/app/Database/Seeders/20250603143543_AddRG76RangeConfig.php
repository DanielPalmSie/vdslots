<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG76RangeConfig extends Seeder
{
    private array $new_configs;
    private string $trigger_name = 'RG76';
    private string $old_trigger_limit_name = "RG76-multiplier";
    private string $new_trigger_limit_name = "RG76-min-multiplier";

    /**
     * @throws JsonException
     */
    public function init()
    {
        $this->new_configs = [
            [
                "config_name" => "{$this->trigger_name}-max-multiplier",
                "config_tag" => 'RG',
                "config_value" => "UKGC:4999;SGA:4999;DGA:4999;DGOJ:4999;AGCO:4999;MGA:4999;",
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
        foreach ($this->new_configs as $config) {
            Config::create($config);
        }

        Config::where('config_tag', 'RG')
            ->where('config_name', $this->old_trigger_limit_name)
            ->update(['config_name' => $this->new_trigger_limit_name]);
    }

    public function down()
    {
        foreach ($this->new_configs as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }

        Config::where('config_tag', 'RG')
            ->where('config_name', $this->new_trigger_limit_name)
            ->update(['config_name' => $this->old_trigger_limit_name]);
    }
}
