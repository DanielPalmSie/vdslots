<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

/**
 * ./console seeder:up 20250103100514
 * 
 * ./console seeder:down 20250103100514
 */
class AddSportsBettingHistoryPageKunga extends Seeder
{
    private string $brand;
    private string $table;
    private Connection $connection;

    public function init(): void
    {
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'menus';
        $this->connection = DB::getMasterConnection();
    }

    public function up(): void
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where('name', '#sports-betting-history')
            ->update([
                'included_countries' => 'SE',
                'excluded_countries' => '',
                'excluded_provinces' => null
            ]);
    }

    public function down(): void
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->where('name', '#sports-betting-history')
            ->update([
                'included_countries' => '',
                'excluded_countries' => 'ES IT DE DK NL SE',
                'excluded_provinces' => 'CA-ON'
            ]);
    }
}