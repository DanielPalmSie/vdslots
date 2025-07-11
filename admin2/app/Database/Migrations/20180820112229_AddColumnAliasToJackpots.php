<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnAliasToJackpots extends Migration
{
    protected $table;
    protected $schema;
    
    
    public function init()
    {
        $this->table = 'jackpots';
        $this->schema = $this->get('schema');
    }
    
    
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('jpalias',55);
            $table->index('jpalias');  
        });
    }
    
    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('jpalias');
        });
    }
}
