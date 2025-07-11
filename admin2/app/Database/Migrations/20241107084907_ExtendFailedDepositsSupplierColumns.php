<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ExtendFailedDepositsSupplierColumns extends Migration
{
    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        if ($this->schema->hasTable('failed_deposits')) {
            $this->schema->table('failed_deposits', function (Blueprint $table) {
                $table->asMaster();

                $table->string('dep_type', 63)->change();
            });
        }
    }

    public function down()
    {

    }
}
