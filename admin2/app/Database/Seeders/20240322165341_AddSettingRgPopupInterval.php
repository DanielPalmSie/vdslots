<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class AddSettingRgPopupInterval extends Seeder
{
    private array $config;

    public function init()
    {
        $this->config = [
            'config_name' => 'popupsInterval',
            'config_tag' => 'RG',
            'config_value' => 60,
            'config_type' => json_encode([
                "type" => "number",
            ], JSON_THROW_ON_ERROR)
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