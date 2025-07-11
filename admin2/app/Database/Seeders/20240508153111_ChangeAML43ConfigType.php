<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class ChangeAML43ConfigType extends Seeder
{
    private array $config_names;

    public function init()
    {
        $this->config_names = ['AML43-target', 'AML43-frequency'];
    }

    public function up()
    {
        Config::whereIn('config_name', $this->config_names)->update([
            'config_type' => json_encode([
                "type" => "text",
            ], JSON_THROW_ON_ERROR)]);
    }

    public function down()
    {
        Config::whereIn('config_name', $this->config_names)->update([
            'config_type' => json_encode([
            "type" => "number",
        ], JSON_THROW_ON_ERROR)]);
    }
}