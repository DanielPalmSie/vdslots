<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class NotFoundPageTextToLocalizedStringsConnections extends Migration
{
    /** @var string */
    protected $table;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings_connections';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)->insert([
                'target_alias' => '404.header',
                'bonus_code' => 0,
                'tag' => 'not.found'
            ]);

        $this->connection
            ->table($this->table)->insert([
                'target_alias' => '404.content.html',
                'bonus_code' => 0,
                'tag' => 'not.found'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('target_alias', '=', '404.header')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'not.found')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('target_alias', '=', '404.content.html')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'not.found')
            ->delete();
    }
}
