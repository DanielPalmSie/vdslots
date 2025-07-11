<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddTypeToRegulatoryStatsTable extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'regulatory_stats';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'type')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->string('type');
                $table->string('value')->change(); // before was INT, and doesn't support float or formatted strings (Ex. for average/median on point 13)
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'type')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('type');
            });
        }
    }
}

