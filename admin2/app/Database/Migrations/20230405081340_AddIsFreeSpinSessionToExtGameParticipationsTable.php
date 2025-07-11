<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class AddIsFreeSpinSessionToExtGameParticipationsTable extends Migration
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
        if (!$this->schema->hasColumn($this->table, 'is_free_spin_session')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->boolean('is_free_spin_session')->default(false);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'is_free_spin_session')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('is_free_spin_session');
            });
        }
    }
}
