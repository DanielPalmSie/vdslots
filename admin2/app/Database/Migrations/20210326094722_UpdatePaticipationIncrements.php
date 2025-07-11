<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class UpdatePaticipationIncrements extends Migration
{
    protected $table;
    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'ext_game_participations_increments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasColumn($this->table, 'participation_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigInteger('participation_id')->change();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'participation_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('participation_id', 50)->change();
            });
        }
    }
}
