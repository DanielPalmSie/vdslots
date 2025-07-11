<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class CreateSportListTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;

    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'sport_sports_list';
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
            $table->string('ext_id', 191)->unique();
            $table->string('original_name', 255);
            $table->string('name', 191)->unique();
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
