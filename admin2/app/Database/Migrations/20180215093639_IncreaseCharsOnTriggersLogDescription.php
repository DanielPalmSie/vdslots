<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class IncreaseCharsOnTriggersLogDescription extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'triggers_log';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->string('descr', 256)->change();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->string('descr', 100)->change();
        });
    }
}
