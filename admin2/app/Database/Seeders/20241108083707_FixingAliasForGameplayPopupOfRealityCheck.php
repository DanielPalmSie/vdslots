<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class FixingAliasForGameplayPopupOfRealityCheck extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $oldAlias;
    private string $newAlias;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->oldAlias = 'reality-check.label.responsiblegaming';
        $this->newAlias = 'reality-check.label.responsibleGaming';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->init();

        $this->connection
            ->table($this->table)
            ->where('alias', $this->oldAlias)
            ->update(['alias' => $this->newAlias]);
    }
}
