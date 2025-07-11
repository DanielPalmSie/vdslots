<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;

class AddIndexesToPendingWithdrawals extends Migration
{
    protected string $table;


    protected Builder $schema;

    public function init()
    {
        $this->table = 'pending_withdrawals';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->index('mb_email', 'idx_mb_email');
            $table->index('net_account', 'idx_net_account');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex('idx_mb_email');
            $table->dropIndex('idx_net_account');
        });
    }
}
