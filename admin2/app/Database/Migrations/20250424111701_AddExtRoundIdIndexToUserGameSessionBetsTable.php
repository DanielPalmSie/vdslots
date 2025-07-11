<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\MysqlBuilder;
use App\Extensions\Database\Schema\Blueprint;

class AddExtRoundIdIndexToUserGameSessionBetsTable extends Migration
{
    private string $table = 'user_game_session_bets';
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->index('ext_round_id', 'user_game_session_bets_ext_round_id_index');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex('user_game_session_bets_ext_round_id_index');
        });
    }
}
