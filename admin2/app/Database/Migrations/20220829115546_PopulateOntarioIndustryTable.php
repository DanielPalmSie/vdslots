<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class PopulateOntarioIndustryTable extends Migration
{
    const LICENSE = 'ca';
    const CONFIG_TAG = 'industries';
    const CONFIG_TYPE = '{"type" : "json"}';

    /**
     * Do the migration
     */
    protected $table;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Do the migration
     */
    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'license_config';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        /**
         * @param Collection $data
         * @return mixed
         */
        $bulkInsertInMasterAndShards = function ($data) {
            DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
            DB::bulkInsert($this->table, null, $data->toArray());
            return $data;
        };

        $bulkInsertInMasterAndShards($this->getIndusty());
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)
            ->where('license', '=', self::LICENSE)
            ->where('config_tag', '=', self::CONFIG_TAG)
            ->delete();
    }

    private function generateInserts()
    {
        $data = $this->loadJson();
        $inserts = [];
        foreach ($data as $industry) {
            $inserts[] = [
                'license' => self::LICENSE,
                'config_name' => $industry['name'],
                'config_tag' => self::CONFIG_TAG,
                'config_value' => json_encode([
                    'industry' => $industry['name']
                ]),
                'config_type' => self::CONFIG_TYPE
            ];
        }
        return $inserts;
    }

    /**
     * @return array
     */
    private function loadJson()
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../data/canadaIndusties.json'),
            true
        );
    }

    /**
     * @return Collection
     */
    private function getIndusty()
    {
        return collect($this->generateInserts());
    }

}
