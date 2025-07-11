<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateOverrideAmountsConfig extends Migration
{
    private Connection $connection;
    private string $brand;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'config';
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand === 'kungaslottet') {
            $existingConfig = $this->connection->table($this->table)
                ->where('config_name', 'override-amounts')
                ->where('config_tag', 'cashier')
                ->first();

            if ($existingConfig) {
                $configEntries = explode("\n", $existingConfig->config_value);
                $updatedConfigEntries = [];

                foreach ($configEntries as $entry) {
                    list($currency, $values) = explode('::', $entry, 2);

                    if ($currency === 'SEK') {
                        $values = '250,500,1000,2500';
                    }

                    $updatedConfigEntries[] = $currency . '::' . $values;
                }

                $updatedConfigValue = implode("\n", $updatedConfigEntries);

                $this->connection->table($this->table)
                    ->where('config_name', 'override-amounts')
                    ->where('config_tag', 'cashier')
                    ->update(['config_value' => $updatedConfigValue]);
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $existingConfig = $this->connection->table($this->table)
                ->where('config_name', 'override-amounts')
                ->where('config_tag', 'cashier')
                ->first();

            if ($existingConfig) {
                $configEntries = explode("\n", $existingConfig->config_value);
                $revertedConfigEntries = [];

                foreach ($configEntries as $entry) {
                    list($currency, $values) = explode('::', $entry, 2);

                    if ($currency === 'SEK') {
                        // Revert SEK values to original
                        $values = '250,500,1000,2500,5000,10000';
                    }

                    $revertedConfigEntries[] = $currency . '::' . $values;
                }

                $revertedConfigValue = implode("\n", $revertedConfigEntries);

                $this->connection->table($this->table)
                    ->where('config_name', 'override-amounts')
                    ->where('config_tag', 'cashier')
                    ->update(['config_value' => $revertedConfigValue]);
            }
        }
    }
}
