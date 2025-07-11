<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class UpdateExtGameParticipations extends Migration
{
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
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                    $connection->statement("ALTER TABLE `{$this->table}` DROP COLUMN `ext_session_id`");
                    $connection->statement("ALTER TABLE `{$this->table}` CHANGE `parent_id` `external_game_session_id` INT(10) UNSIGNED NOT NULL");
                    $connection->statement("ALTER TABLE `{$this->table}` CHANGE `ext_id` `user_game_session_id` INT(10) UNSIGNED NOT NULL");
                }, false);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                    $connection->statement("ALTER TABLE `{$this->table}` ADD COLUMN `ext_session_id` VARCHAR(50) NOT NULL AFTER `participation_id`");
                    $connection->statement("ALTER TABLE `{$this->table}` CHANGE `external_game_session_id` `parent_id` INT(10) UNSIGNED NULL");
                    $connection->statement("ALTER TABLE `{$this->table}` CHANGE `user_game_session_id` `ext_id` VARCHAR(50) NOT NULL");
                }, false);
            });
        }
    }
}
