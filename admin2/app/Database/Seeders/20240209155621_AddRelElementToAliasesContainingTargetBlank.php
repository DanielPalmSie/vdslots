<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;

class AddRelElementToAliasesContainingTargetBlank extends Seeder
{
    private string $table;
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('value', 'LIKE', '%target="_blank"%')
            ->where('value', 'NOT LIKE', '%rel="noopener noreferrer"%')
            ->update(['value' => DB::raw("REPLACE(value, 'target=\"_blank\"', 'target=\"_blank\" rel=\"noopener noreferrer\"')")]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('value', 'LIKE', '%target="_blank" rel="noopener noreferrer"%')
            ->update(['value' => DB::raw("REPLACE(value, 'target=\"_blank\" rel=\"noopener noreferrer\"', 'target=\"_blank\"')")]);
    }

}
