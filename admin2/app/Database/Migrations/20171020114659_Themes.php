<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class Themes extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'themes';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->increments('id');
            $table->string('name', 80);
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
