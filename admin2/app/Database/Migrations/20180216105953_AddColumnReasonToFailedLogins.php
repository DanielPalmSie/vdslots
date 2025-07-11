<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddColumnReasonToFailedLogins extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'failed_logins';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('reason_tag', 50);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->dropColumn('reason_tag');
        });
    }
}
