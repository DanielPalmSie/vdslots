<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\MysqlBuilder;
use Illuminate\Database\Schema\Blueprint;

class UserGameSessionBets extends Migration
{
    protected MysqlBuilder $schema;
    protected $table;

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
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->unsignedBigInteger('bet_id');
                $table->unsignedBigInteger('session_id');
                $table->timestamp('created_at')->useCurrent();
                $table->index('bet_id');
                $table->index('session_id');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
    }
}
