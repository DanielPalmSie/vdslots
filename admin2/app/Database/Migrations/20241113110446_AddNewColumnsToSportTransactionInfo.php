<?php

/**
 * ./console mig:up 20241113110446
 * 
 * ./console mig:down 20241113110446
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddNewColumnsToSportTransactionInfo extends Migration
{
    protected Builder $schema;
    private string $table;
    private string $defaultNetwork;

    public function init(): void
    {
        $this->table = 'sport_transaction_info';
        $this->schema = $this->get('schema');
        $this->defaultNetwork = 'poolx';
    }

    /**
     * Do the migration
     */
    public function up(): void
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('network')
                ->after('sport_transaction_id')
                ->default($this->defaultNetwork)
                ->index();
        });

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->bigInteger('ticket_id')
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
            $table->dropColumn('network');
        });

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('ticket_id');
        });
    }
}
