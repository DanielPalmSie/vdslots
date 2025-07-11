<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class RemoveRG20SportsbookCountriesSetting extends Seeder
{
    private array $config;

    public function init()
    {
        $this->config = [
            'config_name' => 'RG20-sportsbook-countries',
            'config_tag' => 'RG',
            'config_value' => 'GB SE MT',
            'config_type' => json_encode([
                "type" => "ISO2",
                "delimiter" => " ",
            ])
        ];
    }

    public function up()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }

    public function down()
    {
        Config::create($this->config);
    }

}
