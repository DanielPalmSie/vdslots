<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEmailTemplateTable extends Migration
{
    /**
     * Do the migration
     */

    public function init()
    {
        $this->table = 'email_templates';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 32);
            $table->string('hash', 32);
            $table->text('metadata');
            $table->string('language', 2);
            $table->text('template');
            $table->mediumtext('html');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
//        $this->schema->drop($this->table);
    }
}
