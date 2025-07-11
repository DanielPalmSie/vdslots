<?php

use Illuminate\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateExtGameParticipationBonuses extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'ext_game_participations_bonuses';
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
                $table->unsignedInteger('ext_game_participation_id')->index();
                $table->unsignedInteger('bonus_entry_id')->index();
                $table->unsignedInteger('balance_start')->default(0);
                $table->unsignedInteger('balance_end')->default(0);
                $table->unsignedInteger('won_amount')->default(0);
                $table->unique(['ext_game_participation_id', 'bonus_entry_id'], 'participation_bonuses_unique');
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
