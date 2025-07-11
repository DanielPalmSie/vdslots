<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

use App\Extensions\Database\Connection\Connection;

class AmlGrsHighRiskProfileFraudFlag extends Seeder
{
    private Connection $connection;
    private string $table;

    private const FLAG = 'aml-grs-high-risk-profile-fraud-flag';

    private array $config = [
        [
            'config_name' => 'enabled-' . self::FLAG,
            'config_tag' => 'withdrawal-flags',
            'config_type' => '{"type":"choice","values":["on","off"]}',
            'config_value' => 'on',
        ],
        [
            'config_name' => 'high-risk-profile-fraud-flag-days',
            'config_tag' => 'withdrawal-flags',
            'config_type' => '{"type":"number"}',
            'config_value' => 30,
        ]
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
