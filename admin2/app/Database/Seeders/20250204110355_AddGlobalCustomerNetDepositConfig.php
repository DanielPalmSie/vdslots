<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddGlobalCustomerNetDepositConfig extends Seeder
{
    public function up()
    {
        $config = [
            "config_name" => 'global-customer-net-deposit',
            "config_tag" => 'RG',
            "config_value" => '',
            "config_type" => json_encode([
                "type" => "template",
                "delimiter" => "::",
                "next_data_delimiter" => ";",
                "format" => "<:Jurisdiction><delimiter><:MaxLimit>"
            ], JSON_THROW_ON_ERROR),
        ];

        Config::shs()->insert($config);
    }

    public function down()
    {
        Config::shs()->where("config_name", "global-customer-net-deposit")->delete();
    }
}