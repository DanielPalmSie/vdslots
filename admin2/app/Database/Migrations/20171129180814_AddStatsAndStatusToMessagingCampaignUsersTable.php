<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddStatsAndStatusToMessagingCampaignUsersTable extends Migration
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
            $table->string('message_id', 100);
            $table->string('status', 50);
            $table->bigInteger('open')->default(0);
            $table->bigInteger('click')->default(0);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('message_id');
            $table->dropColumn('status');
            $table->dropColumn('open');
            $table->dropColumn('click');
        });
    }
}


