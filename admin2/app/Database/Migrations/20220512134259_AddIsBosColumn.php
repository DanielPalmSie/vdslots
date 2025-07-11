<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIsBosColumn extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'slow_game_replies';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->boolean('is_bos')->default(0)->after('method');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('is_bos');
        });
    }
}
