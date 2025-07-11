<?php

/**
 * ./console mig:up 20241204145100
 *
 * ./console mig:down 20241204145100
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddTransactionTypeColumnToSportTransactionInfo extends Migration
{

    protected Builder $schema;
    private string $table;

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
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('transaction_type')
                ->after('network')
                ->nullable()
                ->index();
        });
    }

    /**
     * Undo the migration
     */
    public function down(): void
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('transaction_type');
        });
    }
}
