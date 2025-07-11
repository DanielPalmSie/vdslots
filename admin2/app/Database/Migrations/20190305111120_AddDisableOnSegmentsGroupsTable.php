<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddDisableOnSegmentsGroupsTable extends Migration
{
    protected $table;
    protected $schema;


    public function init()
    {
        $this->table = 'segments_groups';
        $this->schema = $this->get('schema');
    }


    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->boolean('disabled')->default(false);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('disabled');
        });
    }
}
