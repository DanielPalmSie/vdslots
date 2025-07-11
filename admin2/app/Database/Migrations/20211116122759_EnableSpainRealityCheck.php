<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class EnableSpainRealityCheck extends Migration
{
    /** @var string */
    protected $table;

    /** @var string */
    protected $config_name;

    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->table = 'config';
        $this->config_name = 'reality-check-countries';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $countries = $this->getCountries();

        if (in_array('ES', $countries)) {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where('config_name', '=', $this->config_name)
            ->update(
                [
                    'config_value' => join(' ', [...$countries, 'ES'])
                ]
            );
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $countries = $this->getCountries();

        if (!in_array('ES', $countries)) {
            return;
        }

        unset($countries[array_search('ES', $countries)]);

        $this->connection
            ->table($this->table)
            ->where('config_name', '=', $this->config_name)
            ->update(
                [
                    'config_value' => join(' ', $countries)
                ]
            );
    }

    /**
     * @return array
     */
    private function getCountries(): array
    {
        $config = $this->connection
            ->table($this->table)
            ->where('config_name', '=', $this->config_name)
            ->get()
            ->first()
        ;

        if (empty($config)) {
            return [];
        }

        return explode(' ', $config->config_value);
    }
}
