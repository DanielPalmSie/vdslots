<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddAllowedRollbacksAmountRuleForWithdrawConfig extends Seeder
{
    private Connection $connection;
    private string $table = 'config';

    private array $config = [
        'config_name' => 'rollbacks-amount-euro-cents',
        'config_tag' => 'withdrawal-flags',
        'config_type' => '{"type":"number"}',
        'config_value' => '5000'
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        parent::up();
        $this->connection->table($this->table)->insert($this->config);
    }

    public function down()
    {
        parent::down();
        $this->connection->table($this->table)
            ->where('config_name', '=', $this->config['config_name'])
            ->where('config_tag', '=', $this->config['config_tag'])
            ->delete();
    }
}
