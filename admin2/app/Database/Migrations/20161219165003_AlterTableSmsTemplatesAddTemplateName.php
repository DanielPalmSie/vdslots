<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AlterTableSmsTemplatesAddTemplateName extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'sms_templates';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('template_name', 256)->nullable()->after('id');
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
