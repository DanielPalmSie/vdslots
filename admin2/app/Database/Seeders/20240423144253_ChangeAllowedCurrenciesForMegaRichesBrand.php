<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ChangeAllowedCurrenciesForMegaRichesBrand extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;
    private array $allowed_currencies;

    public function init()
    {
        $this->table = 'currencies';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->allowed_currencies = ['EUR', 'GBP', 'SEK', 'DKK'];
    }

    public function up()
    {
        if ($this->brand === 'megariches') {
            $this->connection
                ->table($this->table)
                ->whereNotIn('code', $this->allowed_currencies)
                ->update([
                    'legacy' => 1
                ]);
        }
    }
}
