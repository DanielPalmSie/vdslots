<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class ExternalRegulatoryReportLogs extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'external_regulatory_report_logs';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->string('unique_id', 255);
            $table->string('regulation', 100);
            $table->string('report_type', 100);
            $table->string('report_data_from', 100);
            $table->string('report_data_to', 100);
            $table->timestamp('created_at')->useCurrent();
            $table->integer('sequence')->nullable();
            $table->string('filename_prefix', 100)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->text('log_info')->nullable();

            $table->index('unique_id');
            $table->index('report_type');
            $table->index('report_data_from');
            $table->index('report_data_to');
            $table->index('created_at');
            $table->index('sequence');
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
