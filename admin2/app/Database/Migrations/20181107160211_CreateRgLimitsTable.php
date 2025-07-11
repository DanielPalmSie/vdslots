<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class CreateRgLimitsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'rg_limits';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->bigInteger("cur_lim");
            $table->bigInteger("new_lim");
            $table->string("time_span", 10);
            $table->bigInteger("progress");
            $table->timestamp("started_at")->default('0000-00-00 00:00:00');
            $table->timestamp("resets_at")->default('0000-00-00 00:00:00');
            $table->timestamp("changes_at")->default('0000-00-00 00:00:00');
            $table->string("type", 25);
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->unique(['user_id', 'type', 'time_span'], 'user_id');
            $table->index('resets_at', 'resets_at');
            $table->index('changes_at', 'changes_at');
            $table->index('updated_at', 'updated_at');
            $table->index('created_at', 'created_at');
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
