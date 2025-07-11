<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddPayoutColumnRaceEntry extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'race_entries';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->tinyInteger('payed_out')->default(0);
        });

        // Set payed_out 1 for all entries with end_time < now.
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('payed_out');
        });
    }
}
