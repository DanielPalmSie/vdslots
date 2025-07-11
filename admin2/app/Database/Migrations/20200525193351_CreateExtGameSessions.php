<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class CreateExtGameSessions extends Migration
{
    protected $table;
    protected $table_participations;
    protected $table_increments;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'ext_game_sessions';
        $this->table_participations = 'ext_game_participations';
        $this->table_increments = 'ext_game_participations_increments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->bigIncrements('id');
                $table->string('ext_session_id', 50)->index(); // aams session_id
                $table->string('ext_game_id', 70); // boodofdead
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
                $table->timestamp('ended_at')->default('0000-00-00 00:00:00');
            });
        }
        if (!$this->schema->hasTable($this->table_participations)) {
            $this->schema->create($this->table_participations, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->string('participation_id', 50)->index(); // aams participation_id
                $table->string('ext_session_id', 50)->index(); // aams session_id (same as on ext_game_sessions)
                $table->unsignedInteger('parent_id')->nullable(); // refers to the ext_game_sessions id
                $table->string('token_id', 70)->index(); // game provider token
                $table->bigInteger('user_id')->index();
                $table->unsignedInteger('balance')->default(0);
                $table->unsignedInteger('stake')->default(0);
                $table->string('ext_id', 50); // our game_session_id
                $table->string('ext_game_id', 70);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
                $table->timestamp('ended_at')->default('0000-00-00 00:00:00');
            });

            DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                $connection->statement("ALTER TABLE `ext_game_participations` CONVERT TO CHARACTER SET utf8;");
            }, false);
        }
        if (!$this->schema->hasTable($this->table_increments)) {
            $this->schema->create($this->table_increments, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->index();
                $table->string('participation_id', 50)->index(); // aams participation_id
                $table->unsignedInteger('increment')->default(0); // stake increment
                $table->unsignedInteger('stake')->default(0); // including both bonus when applicable
                $table->unsignedInteger('balance')->default(0);
                $table->unsignedInteger('stake_balance_real_bonus')->default(0);
                $table->unsignedInteger('stake_balance_play_bonus')->default(0);
                $table->timestamp('created_at')->useCurrent();
            });

            DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                $connection->statement("ALTER TABLE `ext_game_participations_increments` CONVERT TO CHARACTER SET utf8;");
            }, false);
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
        if ($this->schema->hasTable($this->table_participations)) {
            $this->schema->drop($this->table_participations);
        }
        if ($this->schema->hasTable($this->table_increments)) {
            $this->schema->drop($this->table_increments);
        }
    }
}
