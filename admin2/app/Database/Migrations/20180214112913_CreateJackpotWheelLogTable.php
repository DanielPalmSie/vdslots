<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateJackpotWheelLogTable extends Migration
{
    protected $table;
    protected $schema;
    
    public function init()
    {
        $this->table = 'jackpot_wheel_log';
        $this->schema = $this->get('schema');
    }
    
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->bigIncrements('id');
            $table->unsignedInteger('wheel_id');
            $table->string('slices',200);
            $table->unsignedInteger('win_segment');
            $table->unsignedInteger('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->string('firstname',45);
            $table->unsignedInteger('win_award_id');
            $table->string('user_currency',4)->nullable();
            $table->unsignedInteger('win_jp_amount')->nullable();
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
