<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class AddRG20SportsbookJurisdictionsSettingOnDbet extends Seeder
{
    private const CONFIG_NAME = 'RG20-sportsbook-jurisdictions';
    private const CONFIG_TAG = 'RG';
    private const CONFIG_VALUE = 'SGA';
    private const BRAND = 'DBET';
    private array $config;

    public function init()
    {
        $this->config = [
            'config_name' => self::CONFIG_NAME,
            'config_tag' => self::CONFIG_TAG,
            'config_value' => self::CONFIG_VALUE,
            'config_type' => json_encode([
                "type" => "ISO2",
                "delimiter" => " ",
            ])
        ];
    }

    public function up(): bool
    {
        if (getenv('APP_SHORT_NAME') !== self::BRAND) {
            return false;
        }

        Config::create($this->config);

        return true;
    }

    public function down(): bool
    {
        if (getenv('APP_SHORT_NAME') !== self::BRAND) {
            return false;
        }

        Config::where('config_name', $this->config['config_name'])->delete();

        return true;
    }

}
