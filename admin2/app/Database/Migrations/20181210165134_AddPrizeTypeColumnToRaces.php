<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddPrizeTypeColumnToRaces extends Migration
{
    protected $races_table;
    protected $race_templates_table;
    protected $schema;
    
    public function init()
    {
        $this->races_table = 'races';
        $this->race_templates_table = 'race_templates';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->races_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->string('prize_type', 10)->after('prizes')->default('cash');
        });
        $this->schema->table($this->race_templates_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->string('prize_type', 10)->after('prizes')->default('cash');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->races_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropColumn('prize_type');
        });
        $this->schema->table($this->race_templates_table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropColumn('prize_type');
        });
    }
}
