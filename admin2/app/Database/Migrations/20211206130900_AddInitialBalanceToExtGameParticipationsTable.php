<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddInitialBalanceToExtGameParticipationsTable extends Migration
{
    /** @var string */
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'ext_game_participations';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'initial_balance')) {

            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->integer('initial_balance')->nullable();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'initial_balance')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('initial_balance');
            });
        }
    }
}