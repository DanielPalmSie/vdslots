<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddTableGameTagsConfig extends Seeder
{
    private Connection $connection;
    private string $table;

    private array $config = [
        [
            'config_name' => 'table-games',
            'config_tag' => 'withdrawal-flags',
            'config_type' => '{"type":"template","next_data_delimiter":",","format":"<:Tag><delimiter>"}',
            'config_value' => 'blackjack,live,live-casino,roulette,table,videopoker',
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
            $this->connection->table($this->table)->insert($config);
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
