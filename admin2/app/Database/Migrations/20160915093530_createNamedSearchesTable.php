<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNamedSearchesTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'named_searches';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {

        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('language', 2);
            $table->text('sql_statement');
            $table->text('form_params');
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
