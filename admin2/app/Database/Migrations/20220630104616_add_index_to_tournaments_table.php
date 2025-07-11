<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddIndexToTournamentsTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'tournaments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function(Blueprint $table)
        {
            $table->index(['category', 'status', 'desktop_or_mobile'], 'status_category_desktopormobile');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table)
        {
            $table->dropIndex('status_category_desktopormobile');
        });
    }
}
