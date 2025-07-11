<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddOverrideCashierPredefinedAmountsConfig extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $configName;
    private string $configTag;

    public function init()
    {
        $this->table = 'config';
        $this->configName = 'override-amounts';
        $this->configTag = 'cashier';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $config = [
            "EUR" => [25, 50, 100, 250, 500, 1000],
            "GBP" => [25, 50, 100, 250, 500, 1000],
            "SEK" => [250, 500, 1000, 2500, 5000, 10000],
            "DKK" => [250, 500, 1000, 2500, 5000, 10000],
            "CAD" => [50, 100, 250, 500, 1000, 2000],
            "NZD" => [50, 100, 250, 500, 1000, 2000],
            "INR" => [2500, 5000, 10000, 25000, 50000, 100000],
            "BRL" => [100, 250, 500, 1000, 2500, 5000]
        ];

        $configData = [
            'config_name' => $this->configName,
            'config_tag' => $this->configTag,
            'config_type' => '{"type":"template", "delimiter":"::", "next_data_delimiter":"\n" , "format":"<:Currency><delimiter><:Amounts>"}',
            'config_value' => $this->formatCurrencyDenominations($config)
        ];
        $this->connection->table($this->table)->insert([$configData]);
    }

    public function down()
    {
        $this->connection->table($this->table)
            ->where('config_tag', '=', $this->configTag)
            ->where('config_name', '=', $this->configName)
            ->delete();
    }

    private function formatCurrencyDenominations($config): string {
        $result = [];

        foreach ($config as $currency => $denominations) {
            $formattedDenominations = [];

            if (!(array_keys($denominations) === range(0, count($denominations) - 1))) {
                foreach ($denominations as $credit => $debit) {
                    $formattedDenominations[] = $credit . ':' . $debit;
                }
            } else {
                $formattedDenominations = $denominations;
            }

            $result[] = $currency . '::' . implode(',', $formattedDenominations);
        }

        return implode(PHP_EOL, $result);
    }
}
