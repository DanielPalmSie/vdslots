<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class AddGameSessionIdColumnToBonusEntriesTable extends Migration
{
    protected string $table;
    protected string $column;
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->table = 'bonus_entries';
        $this->column = 'game_session_id';
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
                $table->unsignedBigInteger($this->column)->nullable()->index();
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
