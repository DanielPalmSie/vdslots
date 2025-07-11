<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateLiabilityReportCache extends Seeder
{

    private Connection $connection;
    private string $tableMiscCache;
    private string $brand;
    private array $miscCacheData;


    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->tableMiscCache = 'misc_cache';
        $this->brand = phive('BrandedConfig')->getBrand();

        $this->miscCacheData = [
            'id_str' => 'liability-report-adjusted-month',
            'cache_value' => '2024-01'
        ];
    }

    public function up()
    {
        if ($this->brand === 'dbet') {
            $isCacheExists = $this->connection
                ->table($this->tableMiscCache)
                ->where('id_str', '=', $this->miscCacheData['id_str'])
                ->exists();

            if (!$isCacheExists) {
                $this->connection->table($this->tableMiscCache)->insert($this->miscCacheData);
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'dbet'){
            $this->connection
                    ->table($this->tableMiscCache)
                    ->where('id_str', '=', $this->miscCacheData['id_str'])
                    ->delete();
        }
    }
}
