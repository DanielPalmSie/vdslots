<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddScheduleTimeToExportTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'export';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dateTime('schedule_time');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('schedule_time');
        });
    }
}
