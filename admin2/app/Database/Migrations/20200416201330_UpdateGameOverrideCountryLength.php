<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class UpdateGameOverrideCountryLength extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
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
            $table->string('country', '3')->change();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('country', '2')->change();
        });
    }
}
