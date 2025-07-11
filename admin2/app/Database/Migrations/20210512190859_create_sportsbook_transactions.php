<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class CreateSportsbookTransactions extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;


    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'sport_transactions';
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
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->string('ticket_type', 128)->index();
            $table->unsignedTinyInteger('ticket_settled')->index()->default(0);
            $table->integer('bet_amount')->default(0);
            $table->integer('win_amount')->default(0);
            $table->boolean('result')->nullable()->index();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
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