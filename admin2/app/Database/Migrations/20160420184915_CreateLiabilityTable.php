<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLiabilityTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'users_monthly_liability';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {

        $this->schema->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->bigInteger('user_id');
            $table->string('type', 10);
            $table->unsignedSmallInteger('main_cat');
            $table->string('sub_cat', 100);
            $table->integer('transactions');
            $table->bigInteger('amount');
            $table->string('currency', 4);
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
