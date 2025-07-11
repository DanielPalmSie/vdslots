<?php

/**
 * ./console mig:up 20240930114022
 */
/**
 * ./console mig:down 20240930114022
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddProductColumnUserDailyStatsSportsTable extends Migration
{
    protected Builder $schema;
    private string $table;
    private string $column;
    private string $defaultNetwork;

    public function init() :void
    {
        $this->table = 'users_daily_stats_sports';
        $this->column = 'product';
        $this->defaultNetwork = 'S';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up() :void
    {
        if (!$this->schema->hasColumn($this->table, $this->column)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->char($this->column, 1)
                    ->after('network')
                    ->default($this->defaultNetwork)
                    ->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down() :void
    {
        if ($this->schema->hasColumn($this->table, $this->column)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->dropColumn($this->column);
            });
        }
    }
}
