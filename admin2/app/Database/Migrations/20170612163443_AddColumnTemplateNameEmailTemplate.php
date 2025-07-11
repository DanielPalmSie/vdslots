<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnTemplateNameEmailTemplate extends Migration
{
    protected $table;

    protected $schema;

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
            $table->string('template_name', 32);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('template_name');
        });
    }
}
