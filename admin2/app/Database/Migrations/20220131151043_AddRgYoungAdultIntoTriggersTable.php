<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddRgYoungAdultIntoTriggersTable extends Migration
{
    private const TRIGGERS_TABLE = 'triggers';
    private const TRIGGER_NAME = 'RG61';

    /**
     * Do the migration
     */
    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $triggers = [
            [
                'name' => self::TRIGGER_NAME,
                'indicator_name' => 'Young adults',
                'description' => 'Players who are young adults (18 up to and including 23 years old)',
                'color' => '#ffffff',
                'score' => 0,
                'ngr_threshold' => 0,
            ]
        ];

        $bulkInsertInMasterAndShards(self::TRIGGERS_TABLE, $triggers);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table(self::TRIGGERS_TABLE)
                ->where('name', self::TRIGGER_NAME)
                ->delete();
        }, true);
    }
}
