<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddOldLimToRgLimitsTable extends Migration
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
            $table->bigInteger('old_lim')->default(0);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->dropColumn('old_lim');
        });
    }
}
