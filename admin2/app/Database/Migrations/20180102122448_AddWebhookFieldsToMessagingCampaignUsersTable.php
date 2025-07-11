<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddWebhookFieldsToMessagingCampaignUsersTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'messaging_campaign_users';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('subject');
            $table->text('html');
            $table->text('text');
            $table->text('smtp_events'); // json encoded array
            $table->text('resends');    // json encoded array
            $table->text('reject');    // set as text as we don't know for sure the length of the reject message

            $table->dropColumn('open');
            $table->dropColumn('click');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('subject');
            $table->dropColumn('html');
            $table->dropColumn('text');
            $table->dropColumn('smtp_events');
            $table->dropColumn('resends');
            $table->dropColumn('reject');

            $table->bigInteger('open')->default(0);
            $table->bigInteger('click')->default(0);
        });
    }
}
