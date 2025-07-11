<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;


class AddAttachmentsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'attachments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {

        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->id();
            $table->integer('email_id')->unsigned();
            $table->binary('data');
            $table->string('file_name');
            $table->string('mime_type');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
    }
}
