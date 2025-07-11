<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveJaAndDeLanguagesFromKS extends Seeder
{
    private Connection $connection;
    private string $table;

    private string $brand;

    public function init()
    {
        $this->table = 'languages';
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->whereIn('language', ['ja', 'de'])
            ->delete();
    }

    public function down()
    {
        if ($this->brand !== 'kungaslottet') {
            return;
        }

        $this->connection
            ->table($this->table)
            ->insert([
                'language' => 'ja',
                'light' => 1,
                'selectable' => 1,
            ]);

        $this->connection
            ->table($this->table)
            ->insert([
                'language' => 'de',
                'light' => 1,
                'selectable' => 1,
            ]);
    }
}
