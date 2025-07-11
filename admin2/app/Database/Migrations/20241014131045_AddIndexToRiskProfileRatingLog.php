<?php

use Illuminate\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddIndexToRiskProfileRatingLog extends Migration
{
    private const TABLE_NAME = 'risk_profile_rating_log';

    /**
     * Do the migration
     */
    public function up()
    {
        $this->get('schema')->table(self::TABLE_NAME, function (Blueprint $table) {
            $table->index(['rating_type', 'created_at']);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->get('schema')->table(self::TABLE_NAME, function (Blueprint $table) {
            $table->dropIndex(['rating_type', 'created_at']);
        });
    }
}
