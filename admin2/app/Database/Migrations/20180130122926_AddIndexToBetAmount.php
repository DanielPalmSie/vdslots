<?php

use Phpmig\Migration\Migration;

class AddIndexToBetAmount extends Migration
{
  
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'users_game_sessions';
        $this->schema = $this->get('schema');
    }
    
    /**
     * Do the migration
     */
    public function up()
    {
      $this->schema->table($this->table, function ($table) {
            $table->asSharded();
            $table->index('bet_amount', 'bet_amount');            
      });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
      $this->schema->table($this->table, function ($table) {
            $table->asSharded();
            $table->dropIndex('bet_amount');
      });

    }
}
