<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddExcludedCountriesToJackpotWheels extends Migration
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
            $table->string('excluded_countries', 255)->index();            
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {			
			$table->dropColumn('excluded_countries');       
        });
    }
}
