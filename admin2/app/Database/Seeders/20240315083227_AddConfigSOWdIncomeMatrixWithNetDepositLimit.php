<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddConfigSOWdIncomeMatrixWithNetDepositLimit extends Seeder
{

    public function init()
    {
    }

    public function up()
    {
        $configValue = [
            '0 - 20,000'        => 0,
            '20,000 - 40,000'   => 0,
            '40,000 - 60,000'   => 0,
            '60,000 - 80,000'   => 0,
            '80,000 - 100,000'  => 0,
            '100,000+'          => 0,
        ];

        $formattedConfigValue = implode(PHP_EOL, array_map(function ($range, $ndl) {
            return "$range::$ndl";
        }, array_keys($configValue), array_values($configValue)));

        $config = [
            "config_name" => '500-affordability-check-GB',
            "config_tag" => 'net-deposit-limit',
            "config_value" => $formattedConfigValue,
            "config_type" => json_encode([
                "type" => "template",
                "delimiter" => "::",
                "next_data_delimiter" => "\n",
                "format" => "<:IncomeRange><delimiter><:CNDL>"
            ], JSON_THROW_ON_ERROR),
        ];

        Config::shs()->insert($config);
    }

    public function down()
    {
        Config::shs()
            ->where("config_name", "500-affordability-check-GB")
            ->where("config_tag", "net-deposit-limit")
            ->delete();
    }
}
