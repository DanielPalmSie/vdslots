<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use Illuminate\Database\Schema\Blueprint;

class DisableFiLanguageForLoggedOutUsers extends Seeder
{
    private $schema;
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->schema = $this->get('schema');
        $this->connection = DB::getMasterConnection();
        $this->table = 'languages';
    }

    public function up()
    {
        // Check if the column exists before trying to update it
        if ($this->schema->hasColumn($this->table, 'logged_out')) {
            $this->connection->table($this->table)
                ->where('language', 'fi')
                ->update(['logged_out' => 0]);
        }
    }

    public function down()
    {
        // Check if the column exists before trying to update it
        if ($this->schema->hasColumn($this->table, 'logged_out')) {
            $this->connection->table($this->table)
                ->where('language', 'fi')
                ->update(['logged_out' => 1]);
        }
    }
}
