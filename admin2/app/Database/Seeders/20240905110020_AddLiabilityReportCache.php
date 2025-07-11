<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddLiabilityReportCache extends Seeder
{
    private Connection $connection;
    private string $brand;
    private array $data;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->data = [
            'id_str' => 'liability-report-adjusted-month',
            'cache_value' => '2024-01'
        ];
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
             $cache_exists = $this->connection
                ->table('misc_cache')
                ->where('id_str', $this->data['id_str'])
                ->exists();

            if (!$cache_exists) {
                $this->connection
                    ->table('misc_cache')
                    ->insert($this->data);
            }
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches'){
            $this->connection
                ->table('misc_cache')
                ->where('id_str', $this->data['id_str'])
                ->delete();
        }
    }
}
