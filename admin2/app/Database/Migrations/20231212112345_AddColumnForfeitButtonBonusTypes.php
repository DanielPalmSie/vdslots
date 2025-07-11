<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddColumnForfeitButtonBonusTypes extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'bonus_types';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->tinyInteger('forfeit_bonus')->default(1)->after('allow_race')->comment('0 - forfeit bonus button not visible, 1 - forfeit bonus button visible');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('forfeit_bonus');
        });
    }
}
