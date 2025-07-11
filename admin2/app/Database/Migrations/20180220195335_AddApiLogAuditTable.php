<?php
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddApiLogAuditTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'external_audit_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster(); // Only in master
            $table->bigIncrements('id');
            $table->string('tag', 100);
            $table->text('request');
            $table->text('response');
            $table->float('response_time');
            $table->integer('status_code');
            $table->bigInteger('user_id')->default(0);
            $table->string('request_id', 50);
            $table->string('response_id', 50);
            $table->timestamp('created_at');
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
