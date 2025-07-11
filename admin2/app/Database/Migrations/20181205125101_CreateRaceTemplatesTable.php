<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateRaceTemplatesTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'race_templates';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->bigIncrements('id');
            $table->string('race_type', 10);
            $table->string('display_as', 25);
            $table->string('levels', 100);
            $table->text('prizes');
            $table->string('game_categories', 128);
            $table->string('games', 250);

            $table->string('recur_type', 10);
            $table->time('start_time');
            $table->date('start_date');
            $table->string('recurring_days', 255);
            $table->timestamp('recurring_end_date')->nullable();
            $table->integer('duration_minutes');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
