<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddRegisterWithMitIdTranslations extends Migration
{
    /** @var string */
    protected $table;
    protected $tableConnections;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->tableConnections = 'localized_strings_connections';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)->insert([
                'alias' => 'dk.register.with.mitid',
                'language' => 'en',
                'value' => 'Register with MitID'
            ]);

        $this->connection
            ->table($this->tableConnections)->insert([
                'target_alias' => 'dk.register.with.mitid',
                'bonus_code' => 0,
                'tag' => 'dk.register'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->tableConnections)
            ->where('target_alias', '=', 'dk.register.with.mitid')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'dk.register')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'dk.register.with.mitid')
            ->where('language', '=', 'en')
            ->delete();
    }
}
