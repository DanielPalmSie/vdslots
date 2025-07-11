<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateEmptyStatusToCorruptedInBonusEntries extends Seeder
{

    private string $table = 'bonus_entries';

    public function up()
    {
        DB::loopNodes(function (Connection $shardConnection) {
            $shardConnection
                ->table($this->table)
                ->where('status', '')
                ->update(['status' => 'corrupted']);
        }, true);
    }

    public function down()
    {
        DB::loopNodes(function (Connection $shardConnection) {
            $shardConnection
                ->table($this->table)
                ->where('status', 'corrupted')
                ->update(['status' => '']);
        }, true);
    }
}



