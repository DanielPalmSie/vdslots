<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG20SportsbookJurisdictionsSetting extends Seeder
{
    private array $config;

    public function init()
    {
        if (getenv('APP_SHORT_NAME') == 'MV') {
            $config_value = 'UKGC MGA';
        } else {
            $config_value = 'UKGC MGA SGA';
        }
        $this->config = [
            'config_name' => 'RG20-sportsbook-jurisdictions',
            'config_tag' => 'RG',
            'config_value' => $config_value,
            'config_type' => json_encode([
                "type" => "ISO2",
                "delimiter" => " ",
            ])
        ];
    }

    public function up()
    {
        Config::create($this->config);
    }

    public function down()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }

}
