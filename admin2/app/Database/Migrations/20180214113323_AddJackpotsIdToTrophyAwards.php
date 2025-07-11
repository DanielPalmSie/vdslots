<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddJackpotsIdToTrophyAwards extends Migration
{
    
    protected $table;
    protected $schema;
    
    
    public function init()
    {
        $this->table = 'trophy_awards';
        $this->schema = $this->get('schema');
    }
    
    
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->bigInteger('jackpots_id');
        });
    }
    
    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('jackpots_id');
        });
    }
}
