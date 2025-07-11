<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class UpdateICSFromExternalRegulatoryReportLogs extends Migration
{
    private const REPORT_TYPES = ['CJD', 'CJT', 'JUD', 'JUT', 'OPT', 'RUD', 'RUT'];
    private const REPORT_TYPE_SUFFIX = '_OLD';

    /** @var string */
    protected $table;

    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->table = 'external_regulatory_report_logs';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach (self::REPORT_TYPES as $report_type) {
            $this->connection->table($this->table)
                ->where('report_type', $report_type)
                ->where('regulation', '!=', 'startICS') // for JUD report
                ->update(['report_type' => $report_type . self::REPORT_TYPE_SUFFIX]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach (self::REPORT_TYPES as $report_type) {
            $this->connection->table($this->table)
                ->where('report_type', $report_type . self::REPORT_TYPE_SUFFIX)
                ->where('regulation', '!=', 'startICS') // for JUD report
                ->update(['report_type' => $report_type]);
        }
    }
}
