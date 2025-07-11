<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddCountryToWheels extends Migration
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
        $this->table = 'jackpot_wheels';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
			$table->asMaster();
            $table->string('country', 3)->index();            
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {			
			$table->dropColumn('country');       
        });
    }
}
