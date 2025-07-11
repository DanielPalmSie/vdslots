<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class AddRgConfigSuspendAccountInHours extends Seeder
{
    private array $config;

    public function init()
    {
        $this->config = [
            "config_name" => 'RG65-suspend-account-in-hours',
            "config_tag" => 'RG',
            "config_value" => 72,
            "config_type" => '{"type":"number"}',
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