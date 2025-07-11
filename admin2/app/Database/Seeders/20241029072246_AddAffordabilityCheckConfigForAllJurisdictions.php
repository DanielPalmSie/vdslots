<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddAffordabilityCheckConfigForAllJurisdictions extends Seeder
{
    private array $configs;

    private array $jurisdictions = ['SGA', 'DGA', 'DGOJ', 'CAON', 'MGA', 'ADM'];

    public function init()
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

        foreach ($this->jurisdictions as $jurisdiction) {
            $this->configs[] = [
                "config_name" => "affordability-check-$jurisdiction",
                "config_tag" => 'net-deposit-limit',
                "config_value" => $formattedConfigValue,
                "config_type" => json_encode([
                    "type" => "template",
                    "delimiter" => "::",
                    "next_data_delimiter" => "\n",
                    "format" => "<:IncomeRange><delimiter><:CNDL>"
                ], JSON_THROW_ON_ERROR),
            ];
        }
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
