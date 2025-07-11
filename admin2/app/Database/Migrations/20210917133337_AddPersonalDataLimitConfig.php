<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddPersonalDataLimitConfig extends Migration
{
    /** @var Connection */
    private $connection;

    private $config_table;

    private $email_config;
    private $daily_limit_config;

    public function init()
    {
        $this->config_table = 'config';
        $this->email_config = [
            'config_name' => 'personal_data_limit_email_list',
            'config_tag' => 'emails',
            'config_type' => '{"type":"template","next_data_delimiter":",","format":"<:Email><delimiter>"}',
            'config_value' => 'example@yopmail.com, example2@yopmail.com, example3@yopmail.com'
        ];
        $this->daily_limit_config = [
            'config_name' => 'display_daily_limit',
            'config_tag' => 'limits',
            'config_type' => '{"type":"number"}',
            'config_value' => '100'
        ];

        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->config_table)
            ->insert($this->email_config);

        $this->connection
            ->table($this->config_table)
            ->insert($this->daily_limit_config);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->config_table)
            ->where('config_name', '=', $this->email_config['config_name'])
            ->where('config_tag', '=', $this->email_config['config_tag'])
            ->delete();

        $this->connection
            ->table($this->config_table)
            ->where('config_name', '=', $this->daily_limit_config['config_name'])
            ->where('config_tag', '=', $this->daily_limit_config['config_tag'])
            ->delete();
    }
}
