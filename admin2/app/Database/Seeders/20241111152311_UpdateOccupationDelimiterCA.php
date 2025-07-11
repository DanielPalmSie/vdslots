<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateOccupationDelimiterCA extends Seeder
{
    private Connection $connection;
    private string $table;

    const LICENSE_CA = 'ca';
    const OCCUPATIONS_CONFIG_TAG = 'occupations';
    const OCCUPATIONS_CONFIG_TYPE = '{"type":"text", "delimiter":":"}';
    const CURRENT_OCCUPATIONS_CONFIG_TYPE = '{"type":"text", "delimiter":","}';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'license_config';
    }


    public function up()
    {
        $bulkInsertInMasterAndShards = function ($data) {
            DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
            DB::bulkInsert($this->table, null, $data->toArray());
            return $data;
        };

        $bulkInsertInMasterAndShards($this->getOccupations());

        // delete existing one config with comma (,) delimiter
        $this->connection
            ->table($this->table)
            ->where('license','=', self::LICENSE_CA)
            ->where('config_tag', '=', self::OCCUPATIONS_CONFIG_TAG)
            ->where('config_type', '=', self::CURRENT_OCCUPATIONS_CONFIG_TYPE)
            ->delete();
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('license','=', self::LICENSE_CA)
            ->where('config_tag', '=', self::OCCUPATIONS_CONFIG_TAG)
            ->where('config_type', '=', self::OCCUPATIONS_CONFIG_TYPE)
            ->delete();
    }

    /**
     * @return array
     */
    private function loadJson(string $fileName)
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../data/'.$fileName),
            true
        );
    }

    private function generateOccupationsInserts(): array
    {
        $data = $this->loadJson('gbOccupations.json');
        $inserts = [];
        foreach ($data as $occupation) {
            $inserts[] = [
                'license' => self::LICENSE_CA,
                'config_name' => $occupation['config_name'],
                'config_tag' => self::OCCUPATIONS_CONFIG_TAG,
                'config_value' => $occupation['config_value'],
                'config_type' => self::OCCUPATIONS_CONFIG_TYPE
            ];
        }
        return $inserts;
    }

    /**
     * @return Collection
     */
    private function getOccupations()
    {
        return collect($this->generateOccupationsInserts());
    }
}
