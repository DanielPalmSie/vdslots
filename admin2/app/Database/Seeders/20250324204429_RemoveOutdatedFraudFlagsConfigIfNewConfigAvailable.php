<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class RemoveOutdatedFraudFlagsConfigIfNewConfigAvailable extends Seeder
{
    private Connection $connection;
    private string $table = 'config';

    private array $configKeys = [
        'enable-total-withdrawal-amount-limit-flag' => 'enabled-total-withdrawal-amount-limit-reached-fraud-flag',
        'enable-negative-balance-since-deposit-fraud-flag' => 'enabled-negative-balance-since-deposit-fraud-flag'
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        foreach ($this->configKeys as $oldKey => $newKey) {
            $newConfig = $this->connection
                ->table($this->table)
                ->where('config_name', $newKey)
                ->first();

            if ($newConfig) {
                $this->connection
                    ->table($this->table)
                    ->where('config_name', $oldKey)
                    ->delete();
            }
        }
    }

    public function down()
    {
        foreach ($this->configKeys as $oldKey => $newKey) {
            $newConfig = $this->connection
                ->table($this->table)
                ->where('config_name', $newKey)
                ->first();

            if ($newConfig) {
                $oldConfig = (array)$newConfig;
                $oldConfig['config_name'] = $oldKey;
                unset($oldConfig['id']);

                $this->connection->table($this->table)->upsert(
                    $oldConfig,
                    ['config_name'],
                    ['config_tag', 'config_type', 'config_value']
                );
            }
        }
    }
}
