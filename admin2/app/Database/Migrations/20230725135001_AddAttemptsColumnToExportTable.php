<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddAttemptsColumnToExportTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'export';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->integer('attempts')->after('status')->default(0);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
}
