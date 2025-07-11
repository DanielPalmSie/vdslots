<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddStatusReasonColumnToExtGameSessions extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'ext_game_sessions';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'status_reason')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->renameColumn('status', 'status_code');
            });
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->unsignedInteger('status_code')->change();
                $table->string('status_reason')
                    ->nullable(true)
                    ->after('status_code');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'status_reason')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->renameColumn('status_code', 'status');
            });
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->integer('status')->change();
                $table->dropColumn('status_reason');
            });
        }
    }
}
