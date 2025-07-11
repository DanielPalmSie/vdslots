<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ExtendDepositsSupplierColumns extends Migration
{
    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        if ($this->schema->hasTable('deposits')) {
            $this->schema->table('deposits', function (Blueprint $table) {
                $table->migrateEverywhere();

                $table->string('dep_type', 63)->change();
            });
        }
    }

    public function down()
    {

    }
}
