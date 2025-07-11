<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddMessagingCampaignIdToQueues extends Migration
{
    /**
     * Do the migration
     */
    protected $mail_queue_table;
    protected $sms_queue_table;

    protected $schema;

    public function init()
    {
        $this->mail_queue_table = 'mailer_queue';
        $this->sms_queue_table = 'sms_queue';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->mail_queue_table, function (Blueprint $table) {
            $table->unsignedBigInteger('messaging_campaign_id')->nullable();
        });
        $this->schema->table($this->sms_queue_table, function (Blueprint $table) {
            $table->unsignedBigInteger('messaging_campaign_id')->nullable();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {

        $this->schema->table($this->mail_queue_table, function (Blueprint $table) {
            $table->dropColumn('messaging_campaign_id');
        });

        $this->schema->table($this->sms_queue_table, function (Blueprint $table) {
            $table->dropColumn('messaging_campaign_id');
        });

    }
}
