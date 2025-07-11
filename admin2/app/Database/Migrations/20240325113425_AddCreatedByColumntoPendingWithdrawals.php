<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;

class AddCreatedByColumntoPendingWithdrawals extends Migration
{
    protected string $table;

    protected string $column;

    protected Builder $schema;

    public function init()
    {
        $this->table = 'pending_withdrawals';
        $this->column = 'created_by';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, $this->column)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->bigInteger($this->column)
                    ->after('approved_by')
                    ->nullable(false)
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
