<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddIndexToBonusEntries extends Migration
{

    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'bonus_entries';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->index('status', 'status');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->dropIndex('status');
        });

    }
}
