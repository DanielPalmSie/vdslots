<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateMailLogTable extends Migration
{
    protected $table;

    protected $table_event;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'mail_log';
        $this->table_event = 'mail_log_events';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->index();
                $table->string('to_address')->index();
                $table->string('supplier', 10)->index();
                $table->string('external_id', 60)->index();
                $table->string('subject')->index();
                $table->text('body');
                $table->text('extra');
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('sent_at')->nullable();
            });
        }

        if (!$this->schema->hasTable($this->table_event)) {
            $this->schema->create($this->table_event, function (Blueprint $table) {
                $table->asMaster();
                $table->bigIncrements('id');
                $table->bigInteger('mail_log_id')->index();
                $table->timestamp('executed_at')->nullable()->index();
                $table->string('type', 50)->index();
                $table->string('response_code', 50)->index();
                $table->text('extra');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
        if ($this->schema->hasTable($this->table_event)) {
            $this->schema->drop($this->table_event);
        }
    }
}
