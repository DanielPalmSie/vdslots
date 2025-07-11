<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

/**
 * ./console seeder:up 20241121114107
 * 
 * ./console seeder:down 20241121114107
 */
class AddSportsBettingHistoryToMenusOnDbet extends Seeder
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
        if ($this->brand !== 'dbet') {
            return;
        }

        /** Enable desktop page */
        $this->connection
            ->table($this->table)
            ->where('alias', 'sports-betting-history')
            ->update([
                'logged_in' => 0,
                'logged_out' => 0,
                'included_countries' => 'SE',
                'excluded_countries' => '',
                'excluded_provinces' => null
            ]);

        /** Enable mobile page */
        $this->connection
            ->table($this->table)
            ->where('alias', 'mobile-sports-betting-history')
            ->update([
                'logged_in' => 0,
                'logged_out' => 0,
                'included_countries' => 'SE',
                'excluded_countries' => '',
                'excluded_provinces' => null
            ]);
    }

    public function down(): void
    {
        if ($this->brand !== 'dbet') {
            return;
        }

        /** Disable desktop page */
        $this->connection
            ->table($this->table)
            ->where('alias', 'sports-betting-history')
            ->update([
                'logged_in' => 1,
                'logged_out' => 1,
                'included_countries' => '',
                'excluded_countries' => 'ES IT DE DK NL SE',
                'excluded_provinces' => 'CA-ON'
            ]);

        /** Disable mobile page */
        $this->connection
            ->table($this->table)
            ->where('alias', 'mobile-sports-betting-history')
            ->update([
                'logged_in' => 1,
                'logged_out' => 1,
                'included_countries' => '',
                'excluded_countries' => 'ES IT DE DK NL SE',
                'excluded_provinces' => 'CA-ON'
            ]);
    }
}