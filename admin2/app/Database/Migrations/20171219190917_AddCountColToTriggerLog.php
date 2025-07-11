<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddCountColToTriggerLog extends Migration
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
        $this->table = 'triggers_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->text('data');
            $table->integer('cnt')->unsigned();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('data');
            $table->dropColumn('cnt');
        });
    }
}
