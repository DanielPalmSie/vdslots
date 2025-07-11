<?php

/**
 * ./console mig:up 20240924140448
 */
/**
 * ./console mig:down 20240924140448
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddNetworkColumnUserDailyStatsSportsTable extends Migration
{
    protected Builder $schema;
    private string $table;
    private string $column;
    private string $defaultNetwork;

    public function init()
    {
        $this->table = 'users_daily_stats_sports';
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
                $table->migrateEverywhere();
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
                $table->migrateEverywhere();
                $table->dropColumn($this->column);
            });
        }
    }
}
