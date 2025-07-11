<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class ReaddedWithdrawalFeatureFlagsConfigWithUpdatedPrefix extends Seeder
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

    public function up(): void
    {
        foreach ($this->configKeys as $oldKey => $newKey) {
            $oldConfig = $this->connection
                ->table($this->table)
                ->where('config_name', $oldKey)
                ->first();

            if ($oldConfig) {
                $newConfig = (array) $oldConfig;
                $newConfig['config_name'] = $newKey;
                unset($newConfig['id']);

                $this->connection->table($this->table)->insert($newConfig);
            }
        }
    }

    public function down(): void
    {
        $this->connection
            ->table($this->table)
            ->whereIn('config_name', array_values($this->configKeys))
            ->delete();
    }
}
