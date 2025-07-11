<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateBoxClassValueInBoxesTable extends Migration
{
    private string $boxes_table = 'boxes';
    private string $pages_table = 'pages';
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $pages_record = $this->getPageRecord();

        $this->connection->table($this->boxes_table)
            ->where('page_id', '=', $pages_record->page_id)
            ->update(['box_class' => 'MobileGeneralBox']);
    }

    private function getPageRecord()
    {
        return $this->connection->table($this->pages_table)
            ->where('cached_path', '=', '/mobile/customer-service')
            ->first();
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $pages_record = $this->getPageRecord();

        $this->connection->table($this->boxes_table)
            ->where('page_id', '=', $pages_record->page_id)
            ->update(['box_class' => 'EmailFormBox']);
    }
}
