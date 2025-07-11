<?php

use Illuminate\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddIndexToTriggersLog extends Migration
{
    const TABLE_NAME = 'triggers_log';
    /**
     * Do the migration
     */
    public function up()
    {
        $this->get('schema')->table(self::TABLE_NAME, function (Blueprint $table) {
            $table->index(['trigger_name', 'created_at']);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->get('schema')->table(self::TABLE_NAME, function (Blueprint $table) {
            $table->dropIndex(['trigger_name', 'created_at']);
        });

    }
}
