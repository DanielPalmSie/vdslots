<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class ChangeAwardIdColInJpSlices extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'jackpot_wheel_slices';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
			$table->asMaster();
            $table->text('award_id')->change();   
            $table->index('wheel_id', 'wheel_id_idx');        
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
			$table->bigInteger('award_id')->change();            
        });
    }
}
