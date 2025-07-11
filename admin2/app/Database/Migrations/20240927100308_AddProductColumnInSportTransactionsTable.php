<?php

/**
 * ./console mig:up 20240927100308
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddProductColumnInSportTransactionsTable extends Migration
{
    protected Builder $schema;
    private string $table;
    private string $column;
    private string $defaultProduct;

    public function init()
    {
        $this->table = 'sport_transactions';
        $this->column = 'product';
        $this->defaultProduct = 'S';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up(): void
    {
        if (!$this->schema->hasColumn($this->table, $this->column)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->char($this->column, 1)
                    ->after('network')
                    ->default($this->defaultProduct)
                    ->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down(): void
    {
        if ($this->schema->hasColumn($this->table, $this->column)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn($this->column);
            });
        }
    }
}
