<?php


use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddContactsListNameToMessagingCampaignTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'messaging_campaigns';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('contacts_list_name');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('contacts_list_name');
        });
    }
}
