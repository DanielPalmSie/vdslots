<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use DB;

class CreateJackpotWheelSlicesTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'jackpot_wheel_slices';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->unsignedInteger('wheel_id');
            $table->unsignedInteger('award_id')->nullable()->default(null);
            $table->integer('probability');
            $table->unsignedInteger('sort_order');
            $table->timestamps();
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