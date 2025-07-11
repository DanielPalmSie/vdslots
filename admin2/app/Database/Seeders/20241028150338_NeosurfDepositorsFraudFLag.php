<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

use App\Extensions\Database\Connection\Connection;

class NeosurfDepositorsFraudFLag extends Seeder
{
    private Connection $connection;
    private string $table;

    private const FLAG = 'neosurf-depositors-fraud-flag';

    private array $config = [
        [
            'config_name' => 'enabled-' . self::FLAG,
            'config_tag' => 'withdrawal-flags',
            'config_type' => '{"type":"choice","values":["on","off"]}',
            'config_value' => 'on',
        ],
    ];

    public function init()
    {
        $this->table = 'config';
        $this->connection = DB::getMasterConnection();
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
    }
}