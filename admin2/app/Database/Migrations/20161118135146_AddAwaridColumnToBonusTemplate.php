<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddAwaridColumnToBonusTemplate extends Migration
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
            $table->unsignedBigInteger('award_id');
            $table->string('template_name');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('award_id');
            $table->dropColumn('template_name');
        });
    }
}
