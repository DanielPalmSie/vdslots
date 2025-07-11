<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class SMSTemplates extends Migration
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
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('language', 2);
            $table->text('template');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
