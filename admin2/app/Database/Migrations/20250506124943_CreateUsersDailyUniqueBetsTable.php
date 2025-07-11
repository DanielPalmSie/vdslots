<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateUsersDailyUniqueBetsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'users_daily_unique_bets';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {

        if (!$this->schema->hasTable($this->table)) {

            $this->schema->create($this->table, function (Blueprint $table) use ($type) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('user_id');
                $table->date('date');
                $table->bigInteger('amount');
                $table->bigInteger('count');
                $table->index(['user_id', 'date']);
                $table->index(['date']); //for DELETE
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
