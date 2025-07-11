<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddForcedUntilToRgLimitsTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'rg_limits';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->timestamp("forced_until")->default('0000-00-00 00:00:00')->after('changes_at');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->dropColumn('forced_until');
        });
    }
}
