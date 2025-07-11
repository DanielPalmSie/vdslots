<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddPersonalDataLimitFeatureFlag extends Migration
{
    /** @var Connection */
    private $connection;

    private $config_table;

    private $daily_limit_feature_flag_config;

    public function init()
    {
        $this->config_table = 'config';
        $this->daily_limit_feature_flag_config = [
            'config_name' => 'is_daily_limit_active',
            'config_tag' => 'personal_data',
            'config_type' => '{"type":"choice","values":["yes","no"]}',
            'config_value' => 'no'
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
            ->insert($this->daily_limit_feature_flag_config);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->config_table)
            ->where('config_name', '=', $this->daily_limit_feature_flag_config['config_name'])
            ->where('config_tag', '=', $this->daily_limit_feature_flag_config['config_tag'])
            ->delete();
    }
}
