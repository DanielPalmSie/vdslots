<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\MysqlBuilder;
use Illuminate\Database\Schema\Blueprint;

class ExtendPendingWithdrawalsSupplierColumns extends Migration
{
    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }
    public function up()
    {
        if ($this->schema->hasTable('pending_withdrawals')) {
            $this->schema->table('pending_withdrawals', function (Blueprint $table) {
                $table->migrateEverywhere();

                $table->string('payment_method', 63)->change();
                $table->string('scheme', 63)->change();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
