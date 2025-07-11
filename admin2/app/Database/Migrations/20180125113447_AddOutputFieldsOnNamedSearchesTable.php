<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddOutputFieldsOnNamedSearchesTable extends Migration
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
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->text('output_fields');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('output_fields');
        });
    }
}
