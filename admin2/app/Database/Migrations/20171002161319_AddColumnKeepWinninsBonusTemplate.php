<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnKeepWinninsBonusTemplate extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'bonus_type_templates';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->unsignedTinyInteger('keep_winnings')->default(0);

        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('keep_winnings');
        });
    }
}
