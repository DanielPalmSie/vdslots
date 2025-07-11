<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class UpdateSelfExclusionAliasValue extends Migration
{

    protected $table;

    protected $connection;

    private $oldValue;
    private $newValue;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->connection = DB::getMasterConnection();

        $this->oldValue = '[HERE]';
        $this->newValue = '[Here]';

    }


    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '{$this->oldValue}', '{$this->newValue}') where language = 'en' AND alias = 'exclude.account.indefinite.info.html' ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->update("UPDATE `{$this->table}` SET value = REPLACE(value, '{$this->newValue}', '{$this->oldValue}') where language = 'en' AND alias = 'exclude.account.indefinite.info.html' ");
    }
}

