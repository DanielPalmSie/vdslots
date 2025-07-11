<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

/**
 * ./console seeder:up 20250415091038
 * ./console seeder:down 20250415091038
 */
class EnableSportsbookMegarichesSweden extends Seeder
{
    private string $brand;
    private Connection $connection;

    public function init(): void
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up(): void
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $this->connection->table('menus')->whereBetween('menu_id', [430, 435])->update([
            'excluded_countries' => 'ES IT DE DK NL'
        ]);
    }

    public function down(): void
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $this->connection->table('menus')->whereBetween('menu_id', [430, 435])->update([
            'excluded_countries' => 'ES IT DE DK NL SE'
        ]);
    }
}