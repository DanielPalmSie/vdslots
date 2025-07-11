<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class MakeAllLanguagesLight extends Migration
{    
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration ./console mig:up 20230109133931
     */
    public function up()
    {
        $this->connection->table('languages')            
             ->update(['light' => 1]);

        foreach(['gameinfo.%', 'game.meta.title.%', 'gametooltip.%'] as $where){
            $this->connection
                 ->table('localized_strings')
                 ->where('alias', 'like', $where)
                 ->delete();
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
	$this->connection->table('languages')            
	     ->whereIn('language', ['en', 'sv', 'fi'])
             ->update(['light' => 0]);        
    }
}
