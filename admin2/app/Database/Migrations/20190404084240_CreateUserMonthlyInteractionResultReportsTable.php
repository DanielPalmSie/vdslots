<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateUserMonthlyInteractionResultReportsTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'users_monthly_interaction_stats';
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
                $table->date('date')->index();
                $table->bigInteger('user_id')->index();
                $table->string('country', 5)->index();
                $table->string('actions', 255);
                $table->string('user_blocks', 255);
                $table->tinyInteger('active');
                $table->tinyInteger('has_limit');
                $table->bigInteger('deposited');
                $table->bigInteger('time_spent');
                $table->bigInteger('total_loss');
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
