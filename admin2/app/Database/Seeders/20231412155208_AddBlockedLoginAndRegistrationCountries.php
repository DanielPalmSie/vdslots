<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;


class AddBlockedLoginAndRegistrationCountries extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $config_name;
    private string $config_tag;

    public function init()
    {
        $this->table = 'config';
        $this->config_name = 'login-and-registration-blocked-countries';
        $this->config_tag = 'exclude-countries';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $row = [
            'config_name' => $this->config_name,
            'config_tag' => $this->config_tag,
            'config_type' => '{"type":"ISO2", "delimiter":" "}',
            'config_value' => ''
        ];
        $this->connection->table($this->table)->insert([$row]);
    }

    public function down()
    {
        $this->connection->table($this->table)
            ->where('config_tag', '=', $this->config_tag)
            ->where('config_name', '=', $this->config_name)
            ->delete();
    }
}
