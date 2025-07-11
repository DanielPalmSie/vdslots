<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class CreateBoAuditLogTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'bo_audit_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->string('actor_id');
            $table->string('action', 20);
            $table->string('target_table');
            $table->bigInteger('target_id');
            $table->text('changes');
            $table->ipAddress('ip');
            $table->timestamp('timestamp')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}