<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddLiveSportToLocalizedStrings extends Migration
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
                'alias' => 'sb.sports.live',
                'language' => 'en',
                'value' => 'Live Sport'
            ]);

        $this->connection
            ->table($this->tableConnections)->insert([
                'target_alias' => 'sb.sports.live',
                'bonus_code' => 0,
                'tag' => 'sb'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->tableConnections)
            ->where('target_alias', '=', 'sb.sports.live')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'sb')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'sb.sports.live')
            ->where('language', '=', 'en')
            ->where('value', '=', 'Live Sport')
            ->delete();
    }
}

