<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

/**
 *
 */
class AddAllowedCountriesConfigForApp extends Migration
{
    /** @var Connection */
    private $connection;

    /**
     * @var
     */
    private $config_table;

    /**
     * @var
     */
    private $allowed_countries_config;

    /**
     * @return void
     */
    public function init()
    {
        $this->config_table = 'config';
        $this->allowed_countries_config = [
            'config_name' => 'allowed-countries',
            'config_tag' => 'app',
            'config_type' => '{"type":"ISO2", "delimiter":" "}',
            'config_value' => 'SE MT'
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
            ->insert($this->allowed_countries_config);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->config_table)
            ->where('config_name', '=', $this->allowed_countries_config['config_name'])
            ->where('config_tag', '=', $this->allowed_countries_config['config_tag'])
            ->delete();
    }
}
