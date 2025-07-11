<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddMessagingUserLink extends Migration
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
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('campaign_id');
            $table->index(['user_id', 'campaign_id']);
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
