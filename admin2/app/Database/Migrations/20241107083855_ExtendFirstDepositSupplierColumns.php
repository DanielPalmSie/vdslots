<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\MysqlBuilder;
use Illuminate\Database\Schema\Blueprint;

class ExtendFirstDepositSupplierColumns extends Migration
{
    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }
    public function up()
    {
        if ($this->schema->hasTable('first_deposits')) {
            $this->schema->table('first_deposits', function (Blueprint $table) {
                $table->migrateEverywhere();

                $table->string('dep_type', 63)->change();
            });
        }
    }

    public function down()
    {

    }
}
