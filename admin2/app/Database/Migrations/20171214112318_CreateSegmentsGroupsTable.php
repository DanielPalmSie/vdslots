<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateSegmentsGroupsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'segments_groups';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->bigInteger('segment_id');
            $table->string('name');
            $table->text('sql_statement');
            $table->text('form_params');
            $table->bigInteger('users_covered');
            $table->timestamps();
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
