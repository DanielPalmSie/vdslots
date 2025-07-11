<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

/**
 * ./console mig:up 20250115165954
 *
 * ./console mig:down 20250115165954
 */
class RemoveForeignKeyFromSportTransactionInfoTable extends Migration
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
     * Do the migration: Remove foreign key.
     */
    public function up(): void
    {
        try {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropForeign(['sport_transaction_id']);
            });
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Undo the migration: Re-add the foreign key.
     */
    public function down(): void
    {
        try {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->foreign('sport_transaction_id')
                    ->references('id')
                    ->on('sport_transactions')
                    ->onDelete('cascade');
            });
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
