<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class RemoveRefreshTimeSegmentsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'segments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('refresh_time');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('refresh_time');
        });
    }
}
