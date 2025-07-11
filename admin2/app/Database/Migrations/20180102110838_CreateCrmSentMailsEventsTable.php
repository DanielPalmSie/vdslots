<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateCrmSentMailsEventsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'crm_sent_mails_events';
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
            $table->string('message_id');
            $table->string('event');
            $table->string('ts');
            $table->string('url');
            $table->string('ip');
            $table->text('location'); // json encoded array
            $table->text('user_agent');
            $table->text('user_agent_parsed'); // json encoded array
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
