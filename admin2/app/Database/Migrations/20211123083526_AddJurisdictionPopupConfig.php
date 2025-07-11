<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddJurisdictionPopupConfig extends Migration
{
    /** @var Connection */
    private $connection;

    private $config_table;

    private $config;

    public function init()
    {
        $this->config_table = 'config';
        $this->config = [
            'config_name' => 'show-jurisdiction-message',
            'config_tag' => 'countries',
            'config_type' => '{"type":"ISO2", "delimiter":" "}',
            'config_value' => ''
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
            ->insert($this->config);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->config_table)
            ->where('config_name', '=', $this->config['config_name'])
            ->where('config_tag', '=', $this->config['config_tag'])
            ->delete();

    }
}
