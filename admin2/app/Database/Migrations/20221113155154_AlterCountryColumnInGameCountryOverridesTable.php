<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class AlterCountryColumnInGameCountryOverridesTable extends Migration
{
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'game_country_overrides';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('country', '7')->change();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('country', '3')->change();
        });
    }
}
