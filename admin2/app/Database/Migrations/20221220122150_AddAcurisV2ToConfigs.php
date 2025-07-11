<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddAcurisV2ToConfigs extends Migration
{
    /** @var Connection */
    private $connection;

    private $config_table;

    private $pep_threshold_refer;
    private $pep_threshold_block;

    public function init()
    {
        $this->config_table = 'config';
        $this->pep_threshold_refer = [
            'config_name' => 'pep_threshold_refer',
            'config_tag' => 'acurisV2',
            'config_value' => '70',
            'config_type' => '{"type":"number"}',
        ];
        $this->pep_threshold_block = [
            'config_name' => 'pep_threshold_block',
            'config_tag' => 'acurisV2',
            'config_value' => '90',
            'config_type' => '{"type":"number"}',
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
            ->insert($this->pep_threshold_refer);

        $this->connection
            ->table($this->config_table)
            ->insert($this->pep_threshold_block);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->config_table)
            ->where('config_name', '=', $this->pep_threshold_refer['config_name'])
            ->where('config_tag', '=', $this->pep_threshold_refer['config_tag'])
            ->delete();

        $this->connection
            ->table($this->config_table)
            ->where('config_name', '=', $this->pep_threshold_block['config_name'])
            ->where('config_tag', '=', $this->pep_threshold_block['config_tag'])
            ->delete();
    }
}
