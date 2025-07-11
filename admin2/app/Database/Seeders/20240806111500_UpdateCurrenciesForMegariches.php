<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateCurrenciesForMegariches extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;
    private array $disabled_currencies;

    public function init()
    {
        $this->table = 'currencies';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->disabled_currencies = ['DKK', 'SEK'];
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->whereIn('code', $this->disabled_currencies)
                ->update([
                    'legacy' => 1
                ]);
        }
    }

    public function down()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->whereIn('code', $this->disabled_currencies)
                ->update([
                    'legacy' => 0
                ]);
        }
    }
}
