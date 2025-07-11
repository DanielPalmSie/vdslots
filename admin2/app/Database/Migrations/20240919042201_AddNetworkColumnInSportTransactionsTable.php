<?php

/**
 * ./console mig:up 20240919042201
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddNetworkColumnInSportTransactionsTable extends Migration
{

    protected Builder $schema;
    private string $table;
    private string $column;
    private string $defaultNetwork;

    public function init()
    {
        $this->table = 'sport_transactions';
        $this->column = 'network';
        $this->defaultNetwork = 'betradar';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, $this->column)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->string($this->column)
                    ->after('id')
                    ->default($this->defaultNetwork)
                    ->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, $this->column)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn($this->column);
            });
        }
    }
}
