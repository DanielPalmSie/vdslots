<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class AddNidToUsers extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'users';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'nid')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->string('nid', 55)->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'nid')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->dropColumn('nid');
            });
        }
    }
}
