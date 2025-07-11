<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ExtendFailedTransactionsSupplierColumns extends Migration
{
    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        if ($this->schema->hasTable('failed_transactions')) {
            $this->schema->table('failed_transactions', function (Blueprint $table) {
                $table->asMaster();

                $table->string('supplier', 63)->change();
                $table->string('scheme', 63)->change();
            });
        }
    }

    public function down()
    {

    }
}
