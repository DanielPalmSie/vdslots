<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG20SportsbookJurisdictionsSettingOnKS extends Seeder
{
    private array $config;

    public function init()
    {
        $this->config = [
            'config_name' => 'RG20-sportsbook-jurisdictions',
            'config_tag' => 'RG',
            'config_value' => 'SGA',
            'config_type' => json_encode([
                "type" => "ISO2",
                "delimiter" => " ",
            ])
        ];
    }

    public function up()
    {
        if (getenv('APP_SHORT_NAME') !== 'KS') {
            return false;
        }

        // Prevent duplicate entry
        if (!Config::where('config_name', $this->config['config_name'])
            ->where('config_tag', $this->config['config_tag'])
            ->exists()) {
            Config::create($this->config);
        }
    }

    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'KS') {
            return false;
        }

        Config::where('config_name', $this->config['config_name'])->delete();
    }

}