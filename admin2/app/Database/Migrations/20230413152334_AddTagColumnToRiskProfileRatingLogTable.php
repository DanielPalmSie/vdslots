<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddTagColumnToRiskProfileRatingLogTable extends Migration
{
    public function init()
    {
        $this->table = 'risk_profile_rating_log';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->string('rating_tag', 100)->nullable();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->dropColumn('rating_tag');
        });
    }
}
