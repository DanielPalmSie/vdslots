<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddBetPlacedAtColumnToSportTransactionsTable extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

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
        if (!$this->schema->hasColumn($this->table, 'bet_placed_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->timestamp('bet_placed_at')->after('created_at')->nullable();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'bet_placed_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('bet_placed_at');
            });
        }
    }
}
