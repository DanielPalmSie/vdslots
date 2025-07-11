<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
class AddDefaultCashierPredefinedAmountsConfig extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $configName;
    private string $configTag;

    public function init()
    {
        $this->table = 'config';
        $this->configName = 'default-amounts';
        $this->configTag = 'cashier';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $default_amounts = [10, 20, 30, 50, 100, 150];

        $configData = [
            'config_name' => $this->configName,
            'config_tag' => $this->configTag,
            'config_type' => '{"type":"text", "delimiter":","}',
            'config_value' => implode(',', $default_amounts)
        ];

        // Check if record exists
        $exists = $this->connection->table($this->table)
            ->where('config_tag', '=', $this->configTag)
            ->where('config_name', '=', $this->configName)
            ->exists();

        if ($exists) {
            // Update existing record
            $this->connection->table($this->table)
                ->where('config_tag', '=', $this->configTag)
                ->where('config_name', '=', $this->configName)
                ->update([
                    'config_type' => $configData['config_type'],
                    'config_value' => $configData['config_value']
                ]);
        } else {
            // Insert new record
            $this->connection->table($this->table)->insert([$configData]);
        }
    }

    public function down()
    {
        $this->connection->table($this->table)
            ->where('config_tag', '=', $this->configTag)
            ->where('config_name', '=', $this->configName)
            ->delete();
    }
}
