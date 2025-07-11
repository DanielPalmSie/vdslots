<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveINRFromCurrencyList extends Seeder
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'currencies';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('code', 'INR')
            ->update([
                'legacy' => 1
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('code', 'INR')
            ->update([
                'legacy' => 0
            ]);
    }
}
