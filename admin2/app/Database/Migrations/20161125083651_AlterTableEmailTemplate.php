<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AlterTableEmailTemplate extends Migration
{

    public function init()
    {
        $this->table = 'email_templates';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->renameColumn('name', 'subject');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->renameColumn('subject', 'name');
        });
    }
}
