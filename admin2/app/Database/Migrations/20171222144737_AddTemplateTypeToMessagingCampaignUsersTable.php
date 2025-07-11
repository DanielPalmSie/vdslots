<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddTemplateTypeToMessagingCampaignUsersTable extends Migration
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
            $table->unsignedTinyInteger('template_type');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('template_type');
        });
    }
}