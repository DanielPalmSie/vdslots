<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveHindiLanguage extends Seeder
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
            ->where('language', 'hi')
            ->delete();
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->insert([
                'language' => 'hi',
                'light' => 1,
                'selectable' => 0,
            ]);
    }
}
