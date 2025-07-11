<?php 

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class SwitchMGAToClosedLoop extends Seeder
{

    private array $countriesToClosedLoop = [];
    private array $fifoCountries = [];

    public function init()
    {
        $this->countriesToClosedLoop = ['AW', 'AX', 'BR', 'BY', 'CA', 'CL', 'EE', 'FI', 'FO', 'GG', 'HR', 'IE', 'IM', 'IN', 'IS', 'JE', 'JP', 'LU', 'MK', 'MT', 'MX', 'MU', 'NO', 'NZ', 'PE', 'PF', 'PY', 'RS', 'SI', 'TW'];
        $this->fifoCountries = explode(' ', phive('Config')->getValue('cashier', 'fifo-countries'));
    }

    public function up()
    {
        $updatedFifoCountriesArray = array_diff($this->fifoCountries, $this->countriesToClosedLoop);

        $configValue = implode(' ', $updatedFifoCountriesArray);

        DB::loopNodes(function ($connection) use ($configValue) {
            $connection->table('config')
                ->where('config_name', '=', 'fifo-countries')
                ->where('config_tag', '=', 'cashier')
                ->update(['config_value' => $configValue]);

        }, true);
    }

    public function down()
    {
        $updatedFifoCountriesArray = array_unique(array_merge($this->fifoCountries, $this->countriesToClosedLoop));
        asort($updatedFifoCountriesArray);

        $configValue = implode(' ', $updatedFifoCountriesArray);

        DB::loopNodes(function ($connection) use ($configValue) {
            $connection->table('config')
                ->where('config_name', '=', 'fifo-countries')
                ->where('config_tag', '=', 'cashier')
                ->update(['config_value' => $configValue]);

        }, true);
    }
}