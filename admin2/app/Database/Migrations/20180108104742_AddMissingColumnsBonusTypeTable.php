<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddMissingColumnsBonusTypeTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'bonus_types';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn('bonus_types', 'keep_winnings'))
        {
            $this->schema->table($this->table, function (Blueprint $table) {

                $table->unsignedTinyInteger('keep_winnings')->default(0);
            });
        }

        if (!$this->schema->hasColumn('bonus_types', 'ext_ids'))
        {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->string('ext_ids');
            });
        }
        if (!$this->schema->hasColumn('bonus_types', 'game_id'))
        {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->string('game_id', 50);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn('bonus_types', 'keep_winnings'))
        {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('keep_winnings');
            });
        }

        if ($this->schema->hasColumn('bonus_types', 'ext_ids'))
        {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('ext_ids');
            });
        }

        if ($this->schema->hasColumn('bonus_types', 'game_id'))
        {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('game_id');
            });
        }
    }
}
