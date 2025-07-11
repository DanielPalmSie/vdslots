<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddContextColumnToBoAuditLogTable extends Migration
{
    private string $table = 'bo_audit_log';
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('context')->nullable();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
}
