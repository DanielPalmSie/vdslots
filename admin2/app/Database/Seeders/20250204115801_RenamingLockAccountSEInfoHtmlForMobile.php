<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RenamingLockAccountSEInfoHtmlForMobile extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $wrongAlias;
    private string $alias;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
        $this->wrongAlias = 'lock.accountSE.info.html';
        $this->alias = 'lock.accountse.info.html';
    }

    public function up()
    {
        $this->init();

        $isAliasWrong = $this->connection
            ->table($this->table)
            ->where('alias', '=', $this->wrongAlias)
            ->exists();

        if ($isAliasWrong) {
            $this->connection
                ->table($this->table)
                ->where('alias', '=', $this->wrongAlias)
                ->update([
                    'alias' => $this->alias,
                ]);
        }
    }
}
