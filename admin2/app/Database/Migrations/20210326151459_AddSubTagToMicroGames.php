<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddSubTagToMicroGames extends Migration
{
    /** @var string */
    protected $games_table;

    /** @var string */
    protected $bonus_table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->games_table = 'micro_games';
        $this->bonus_table = 'bonus_types';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->games_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->string('sub_tag')->after('tag');
        });
/*
        $this->schema->table($this->bonus_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->tinyInteger('tag_type')->default(0);
        });*/
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->games_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropColumn('sub_tag');
        });
/*
        $this->schema->table($this->bonus_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropColumn('tag_type');
        });*/
    }
}
