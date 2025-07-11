<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class AddExtRoundIDToUserGameSessionBets extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'user_game_session_bets';
        $this->schema = $this->get('schema');
    }
    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'ext_round_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('ext_round_id');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'ext_round_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('ext_round_id');
            });
        }

    }
}
