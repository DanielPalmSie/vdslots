<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class CreateSportortsTransactionDetails extends Migration
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
        $this->table = 'sport_transaction_details';
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
            $table->unsignedBigInteger('sport_transaction_id')->index();
            $table->string('event_ext_id')->index();
            $table->string('sport')->index();
            $table->string('category')->nullable()->index();
            $table->string('tournament')->index();
            $table->string('season')->nullable();
            $table->string('market');
            $table->text('competitors')->nullable();
            $table->double('odds')->nullable();

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
