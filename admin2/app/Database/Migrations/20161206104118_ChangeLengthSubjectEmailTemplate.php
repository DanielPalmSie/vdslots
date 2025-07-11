<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ChangeLengthSubjectEmailTemplate extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'email_templates';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('subject', 128)->change();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('subject', 32)->change();
        });

    }
}
