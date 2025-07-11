<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddColumnRaceTemplateToRaces extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'races';
        $this->schema = $this->get('schema');
    }


    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->integer('template_id')->after('id');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropColumn('template_id');
        });
    }
}
