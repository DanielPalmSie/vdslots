<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use DB;

class CreateJackpotWheelsTable extends Migration
{
    protected $table;
    protected $schema;
    
    public function init()
    {
        $this->table = 'jackpot_wheels';
        $this->schema = $this->get('schema');
    }
    
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->unsignedInteger('number_of_slices');
            $table->unsignedInteger('cost_per_spin');   // in whole cents
            $table->tinyInteger('active');
            $table->tinyInteger('deleted');
            $table->timestamps();
        });
    }
    
    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
