<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddEsNewTag extends Seeder
{
    private Connection $connection;
    private string $table = 'game_tags';
    private string $esNewGameTag = 'esnew.cgames';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $row = [
            'alias' => $this->esNewGameTag,
            'filterable' => 1,
        ];

        $this->connection->table($this->table)->insert($row);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', $this->esNewGameTag)
            ->delete();
    }
}
