<?php

use Phpmig\Migration\Migration;
use \App\Extensions\Database\Schema\MysqlBuilder;
use App\Extensions\Database\Schema\Blueprint;

class AddTypeCompletedIdsValidfromValidtoToTrophiesTable extends Migration
{
    private string $table = 'trophies';
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->index(['type', 'completed_ids', 'valid_from', 'valid_to'], 'type_completedids_validfrom_validto');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex('type_completedids_validfrom_validto');
        });
    }
}
