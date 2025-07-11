<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateLocalizedStringsForGamingPopup extends Seeder
{
    private Connection $connection;

    private string $brand;

    private string $table;

    private string $alias;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->table = 'localized_strings';
        $this->alias = 'rg.info.box.last_login_date';
    }

    public function up()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                ->where('alias', $this->alias)
                ->where('language', 'sv')
                ->update([
                    'value' => DB::raw('REPLACE(value, "{{datum}}", "{{date}}")'),
                ]);
        }
    }

    public function down()
    {
        if ($this->brand === 'kungaslottet') {
            $this->connection
                ->table($this->table)
                    ->where('alias', $this->alias)
                    ->where('language', 'sv')
                    ->update([
                        'value' => DB::raw('REPLACE(value, "{{date}}", "{{datum}}")'),
                    ]);
        }
    }
}
