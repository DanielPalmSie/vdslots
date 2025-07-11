<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddSwishAsAutoWithdrawOptionInConfig extends Seeder
{

    private array $configs;

    public function init()
    {
        $this->configs[] = [
            "config_name" => "swish",
            "config_tag" => 'auto-withdraw-option',
            "config_value" => 'yes',
            "config_type" => json_encode(["type"=>"choice", "values"=>["yes","no"]]),
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
