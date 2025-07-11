<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateUserRgEvaluationTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'users_rg_evaluation';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('trigger_name', 6);
            $table->enum('step', ['started', 'self-assessment', 'manual-review']);
            $table->boolean('processed')->default(false);
            $table->boolean('result')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['step', 'processed', 'created_at'], 'step_processed_created_at');
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
