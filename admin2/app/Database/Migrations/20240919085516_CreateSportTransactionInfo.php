<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class CreateSportTransactionInfo extends Migration
{
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;
    
    public function init(): void
    {
        $this->table = 'sport_transaction_info';
        $this->schema = $this->get('schema');
    }
    
    /**
     * Do the migration
     */
    public function up(): void
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sport_transaction_id');
            $table->json('json_data');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));

            $table->foreign('sport_transaction_id')->references('id')->on('sport_transactions')->onDelete('cascade');
        });
    }

    /**
     * Undo the migration
     */
    public function down(): void
    {
        $this->schema->drop($this->table);
    }
}
