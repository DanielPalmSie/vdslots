<?php

use Phpmig\Migration\Migration;

class AddWamountAndRamountIndexToGsessions extends Migration
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
            $table->index('win_amount', 'win_amount');            
            $table->index('result_amount', 'result_amount');            
      });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
      $this->schema->table($this->table, function ($table) {
            $table->asSharded();
            $table->dropIndex('win_amount');
            $table->dropIndex('result_amount');
      });

    }
}
