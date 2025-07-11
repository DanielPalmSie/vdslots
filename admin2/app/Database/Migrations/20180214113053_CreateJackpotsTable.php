<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class CreateJackpotsTable extends Migration
{
    protected $table;
    protected $schema;
    
    public function init()
    {
        $this->table = 'jackpots';
        $this->schema = $this->get('schema');
    }
    
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->string('name', 50)->unique();
            $table->decimal('amount', 24, 12);  // In cents. This allows for a maximum jackpot of 99.9 milion Euro, with a minimum contribution amount of 0.000000000001 cents
            $table->decimal('contribution_share', 5, 4);    // (eg 0.2551 = 25.51%
            $table->decimal('amount_minimum', 24, 12);
            $table->decimal('contribution_next_jp', 5, 4);
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
