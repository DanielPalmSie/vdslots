<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddColumnBetIDToUserGameSessionsWins extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'user_game_session_wins';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'bet_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->unsignedBigInteger('bet_id')->default(0);
                $table->index('bet_id','user_game_session_wins_bet_id_index');
            });
        }

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'bet_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('bet_id');
            });
        }

    }
}
