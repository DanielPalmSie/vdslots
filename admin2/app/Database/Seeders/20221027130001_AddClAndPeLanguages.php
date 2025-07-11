<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddClAndPeLanguages extends Seeder
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'languages';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->insert([
                'language' => 'pe',
                'light' => 1,
                'selectable' => 0
            ]);

        $this->connection
            ->table($this->table)
            ->insert([
                'language' => 'cl',
                'light' => 1,
                'selectable' => 0
            ]);

        $this->connection
            ->table($this->table)
            ->where('language', '=', 'br')
            ->update(['selectable' => 1]);

    }

    public function down()
    {
        $this->connection->table($this->table)
            ->where('language', 'LIKE', 'pe')
            ->delete();

        $this->connection->table($this->table)
            ->where('language', 'LIKE', 'cl')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('language', '=', 'br')
            ->update(['selectable' => 0]);
    }
}
