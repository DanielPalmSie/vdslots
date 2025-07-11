<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;

class FixLocalizedStringsPlaceholders extends Seeder
{
    private Connection $connection;
    private string $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        // issue is present on MGR and DBET
        if (!in_array($this->brand, ['megariches', 'dbet'])) {
            return;
        }

        $this->connection->table($this->table)
            ->where('value', 'LIKE', '%}<}%')
            ->update([
                'value' => DB::raw("REPLACE(value, '}<}', '}}')")
            ]);
    }
}
