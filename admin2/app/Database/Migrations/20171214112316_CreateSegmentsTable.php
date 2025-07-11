<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateSegmentsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'segments';
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
            $table->string('name');
            $table->string('description');
            $table->string('refresh_time');
            $table->bigInteger('users_count');
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
