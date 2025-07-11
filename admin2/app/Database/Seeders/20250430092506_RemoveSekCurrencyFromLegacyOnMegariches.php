<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveSekCurrencyFromLegacyOnMegariches extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;

    public function init()
    {
        $this->table = 'currencies';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->where('code', 'SEK')
                ->update([
                    'legacy' => 0
                ]);
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->where('code', 'SEK')
                ->update([
                    'legacy' => 1
                ]);
        }
    }
}
