<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIndexToUsersComments extends Migration
{
    const TABLE_NAME = 'users_comments';

    /**
     * Do the migration
     */
    public function up()
    {
        $this->get('schema')->table(self::TABLE_NAME, function (Blueprint $table) {
            $table->index(['tag', 'user_id']);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->get('schema')->table(self::TABLE_NAME, function (Blueprint $table) {
            $table->dropIndex(['tag', 'user_id']);
        });
    }
}
