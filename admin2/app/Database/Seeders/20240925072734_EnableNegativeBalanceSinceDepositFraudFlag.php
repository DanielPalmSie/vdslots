<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class EnableNegativeBalanceSinceDepositFraudFlag extends Seeder
{
    private Connection $connection;
    private string $table;
    private bool $isShardedDB;

    private array $config = [
        [
            'config_name' => 'enable-negative-balance-since-deposit-fraud-flag',
            'config_tag' => 'withdrawal-flags',
            'config_type' => '{"type":"choice","values":["on","off"]}',
            'config_value' => 'on',
        ],
    ];

    public function init()
    {
        $this->table = 'config';
        $this->connection = DB::getMasterConnection();
        $this->isShardedDB = $this->getContainer()['capsule.vs.db']['sharding_status'];
    }

    public function up()
    {
        parent::up();

        foreach ($this->config as $config) {
            $this->connection->table($this->table)->upsert(
                $config,
                ['config_name', 'config_tag'],
                ['config_type', 'config_value']
            );
        }

        if ($this->isShardedDB) {
            phive("SQL")->shs()->updateArray('config', ['config_value' => 'on'], ['config_name' => 'enable-negative-balance-since-deposit-fraud-flag']);
        }
    }

    public function down()
    {
        parent::down();

        foreach ($this->config as $config) {
            $this->connection->table($this->table)
                ->where('config_name', '=', $config['config_name'])
                ->where('config_tag', '=', $config['config_tag'])
                ->delete();
        }
    }
}
