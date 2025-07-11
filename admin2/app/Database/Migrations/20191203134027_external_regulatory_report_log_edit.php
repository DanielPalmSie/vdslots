<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class ExternalRegulatoryReportLogEdit extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'external_regulatory_report_logs';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('file_path', 255)->nullable()->change();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
    }
}
