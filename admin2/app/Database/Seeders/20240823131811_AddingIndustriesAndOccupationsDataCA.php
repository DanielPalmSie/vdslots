<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddingIndustriesAndOccupationsDataCA extends Seeder
{

    private Connection $connection;
    private string $brand;
    private string $table;

    const LICENSE = 'ca';
    const INDUSTRIES_CONFIG_TAG = 'industries_list';
    const INDUSTRIES_CONFIG_TYPE = '{"type" : "json"}';

    const OCCUPATIONS_CONFIG_TAG = 'occupations';
    const OCCUPATIONS_CONFIG_TYPE = '{"type":"text", "delimiter":","}';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'license_config';
    }

    public function up()
    {

        $bulkInsertInMasterAndShards = function ($data) {
            DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
            DB::bulkInsert($this->table, null, $data->toArray());
            return $data;
        };

        $bulkInsertInMasterAndShards($this->getIndustries());

        $bulkInsertInMasterAndShards($this->getOccupations());

    }

    public function down()
    {

        $this->connection
            ->table($this->table)
            ->where('license','=', self::LICENSE)
            ->where('config_tag', '=', self::INDUSTRIES_CONFIG_TAG)
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('license','=', self::LICENSE)
            ->where('config_tag', '=', self::OCCUPATIONS_CONFIG_TAG)
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

    private function generateIndustryInserts(): array
    {
        $data = $this->loadJson('gbIndustries.json');
        $inserts = [];
        foreach ($data as $industry) {
            $inserts[] = [
                'license' => self::LICENSE,
                'config_name' => $industry['name'],
                'config_tag' => self::INDUSTRIES_CONFIG_TAG,
                'config_value' => json_encode([
                    'industry' => $industry['name']
                ]),
                'config_type' => self::INDUSTRIES_CONFIG_TYPE
            ];
        }
        return $inserts;
    }

    private function generateOccupationsInserts(): array
    {
        $data = $this->loadJson('gbOccupations.json');
        $inserts = [];
        foreach ($data as $occupation) {
            $inserts[] = [
                'license' => self::LICENSE,
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
    private function getIndustries()
    {
        return collect($this->generateIndustryInserts());
    }


    /**
     * @return Collection
     */
    private function getOccupations()
    {
        return collect($this->generateOccupationsInserts());
    }

}
