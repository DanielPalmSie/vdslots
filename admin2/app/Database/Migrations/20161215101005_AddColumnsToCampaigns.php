<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnsToCampaigns extends Migration
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
            $table->unsignedBigInteger('parent')->nullable()->after('result');
            $table->text('stats')->nullable()->after('parent');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('parent');
            $table->dropColumn('stats');
        });
    }
}
