<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateMailerQueueCrmTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'mailer_queue_crm';
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
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('messaging_campaign_id');
            // email address
            $table->string('from');
            $table->string('from_name');
            // email address
            $table->string('to');
            // email address
            $table->string('reply_to');
            $table->string('subject');
            $table->text('html');
            $table->text('text');
            // this could be the mails.mail_trigger
            $table->string('tag');
            // should be delivered ahead of non-important messages
            // this is the $priority in [0,1] where: 0 - important and 1 - not so important
            // important column is 'boolean' so we have 1 for true and 0 for false
            $table->tinyInteger('important')->default(0);
            // events: track_opens, track_clicks
            $table->tinyInteger('track_events')->default(1);
            $table->timestamps();
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
