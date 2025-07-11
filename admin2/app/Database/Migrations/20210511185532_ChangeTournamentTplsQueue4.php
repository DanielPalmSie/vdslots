<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class ChangeTournamentTplsQueue4 extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'tournament_tpls';
        $this->schema = $this->get('schema');
    }

    public function up()
    {    
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            // ALTER TABLE `tournament_tpls` CHANGE `queue` `queue` VARCHAR(25) NOT NULL
            $table->string('queue', 25)->change();                            
        });    
    }        

    /**
     * Undo the migration
     */
    public function down()
    {
    
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->boolean('queue')->change();                
        });
        
    }
}
