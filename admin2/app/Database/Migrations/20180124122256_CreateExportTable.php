<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateExportTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'export';
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
            $table->string('type', 50);
            $table->bigInteger('target_id');
            $table->tinyInteger('status');
            $table->string('file');
            $table->text('data');
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
