<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use Illuminate\Support\Collection;
use Phpmig\Migration\Migration;

class PopulateProvinceTable extends Migration
{
    const LICENSE = 'it';
    const CONFIG_TAG = 'provinces';
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

        $bulkInsertInMasterAndShards($this->getProvinces());
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
        foreach ($data as $province) {
            $inserts[] = [
                'license' => self::LICENSE,
                'config_name' => $province['denomination'],
                'config_tag' => self::CONFIG_TAG,
                'config_value' => json_encode([
                    'region_code' => $province['region_code'],
                    'iso_province' => $province['automotive_code'],
                    'province_code' => $province['province_code'],
                    'province' => $province['supra_municipal_denomination'],
                    'cadastral_code_municipality' => $province['cadastral_code_municipality']
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
            file_get_contents(__DIR__ . '/../data/provinces.json'),
            true
        );
    }

    /**
     * @return Collection
     */
    private function getProvinces()
    {
        return collect($this->generateInserts());
    }
}
