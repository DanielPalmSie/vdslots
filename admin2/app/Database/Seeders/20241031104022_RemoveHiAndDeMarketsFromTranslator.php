<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class RemoveHiAndDeMarketsFromTranslator extends Seeder
{
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table('permission_groups')
            ->where('tag', 'translate.hi')
            ->orWhere('tag', 'translate.de')
            ->delete();
    }
}
