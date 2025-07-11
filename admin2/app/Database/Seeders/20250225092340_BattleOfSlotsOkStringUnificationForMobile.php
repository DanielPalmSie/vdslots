<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class BattleOfSlotsOkStringUnificationForMobile extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $correctAlias;
    private string $wrongAlias;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->correctAlias = 'ok';
        $this->wrongAlias = 'OK';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->init();

        $this->connection
            ->table($this->table)
            ->where('alias', $this->wrongAlias)
            ->update(['alias' => $this->correctAlias]);
    }
}
