<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateEmptyStatusesToExpiredForMGR extends Seeder
{
    private string $table = 'bonus_entries';

    public function up()
    {
        DB::loopNodes(function (Connection $shardConnection) {
            $shardConnection
                ->table($this->table)
                ->where('status', '')
                ->update(['status' => 'expired']);
        }, true);
    }


    public function down()
    {
        DB::loopNodes(function (Connection $shardConnection) {
            $shardConnection
                ->table($this->table)
                ->where('status', 'expired')
                ->update(['status' => '']);
        }, true);
    }
}
