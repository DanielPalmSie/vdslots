<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Illuminate\Database\Schema\Blueprint;

class AddBlockedCountriesConfigMonthlyWeekOneEmail extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;
    private string $config_name;
    private string $config_tag;
    private array $countries;
    protected $schema;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'config';
        $this->config_name = 'block-monthly-week1';
        $this->config_tag = 'countries';
        $this->countries = ['MT', 'FI', 'GB', 'IE', 'IN', 'NO'];
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->init();

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        // Handle the master change
        $this->updateConfig($this->connection, $this->table);

        // Handle the shards change
        if ($this->schema->hasTable($this->table)) {
            DB::loopNodes(function (Connection $shardConnection) {
                $this->updateConfig($shardConnection, $this->table);
            }, false);
        }
    }

    public function down()
    {
        $this->init();

        if ($this->brand !== 'kungaslottet') {
            return;
        }

        // Rollback the master change
        $this->rollbackConfig($this->connection, $this->table);

        // Rollback the shards change
        if ($this->schema->hasTable($this->table)) {
            DB::loopNodes(function (Connection $shardConnection) {
                $this->rollbackConfig($shardConnection, $this->table);
            }, false);
        }
    }

    private function updateConfig(Connection $connection, string $table)
    {
        $configData = $connection
            ->table($table)
            ->where('config_name', $this->config_name)
            ->where('config_tag', $this->config_tag)
            ->first();

        if (!empty($configData)) {
            $existCountries = explode(' ', $configData->config_value);
            foreach ($this->countries as $country) {
                if (!in_array($country, $existCountries)) {
                    $existCountries[] = $country;
                }
            }

            $newCountriesString = implode(' ', $existCountries);

            $connection
                ->table($table)
                ->where('config_name', $this->config_name)
                ->where('config_tag', $this->config_tag)
                ->update(['config_value' => $newCountriesString]);
        }
    }

    private function rollbackConfig(Connection $connection, string $table)
    {
        $configData = $connection
            ->table($table)
            ->where('config_name', $this->config_name)
            ->where('config_tag', $this->config_tag)
            ->first();

        if (!empty($configData)) {
            $existCountries = explode(' ', $configData->config_value);

            foreach ($this->countries as $country) {
                if (($index = array_search($country, $existCountries)) !== false) {
                    unset($existCountries[$index]);
                }
            }

            $newCountriesString = implode(' ', $existCountries);

            $connection
                ->table($table)
                ->where('config_name', $this->config_name)
                ->where('config_tag', $this->config_tag)
                ->update(['config_value' => $newCountriesString]);
        }
    }

}