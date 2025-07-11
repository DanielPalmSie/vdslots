<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class AlterTablePendingWithdrawals extends Migration
{

    public function init()
    {
        $this->table = 'pending_withdrawals';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {                
        DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {            
            $connection->statement("ALTER TABLE `pending_withdrawals` CHANGE `status` `status` ENUM('approved','disapproved','pending','processing','preprocessing', 'initiated') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'pending'");
        }, false);        
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {            
            $connection->statement("ALTER TABLE `pending_withdrawals` CHANGE `status` `status` ENUM('approved','disapproved','pending','processing','preprocessing') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'pending'");
        }, false);        
    }
}
