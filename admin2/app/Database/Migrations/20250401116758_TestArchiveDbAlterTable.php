<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use \App\Extensions\Database\DualSchemaMigration;

class TestArchiveDbAlterTable extends DualSchemaMigration
{
    protected function defineTable()
    {
        return 'actions'; // users_blocked
    }

    protected function upChanges(Blueprint $table)
    {
        $table->string('test_column', 256)->nullable()->after('id');
    }

    protected function downChanges(Blueprint $table)
    {
        $table->dropColumn('test_column');
    }
}
