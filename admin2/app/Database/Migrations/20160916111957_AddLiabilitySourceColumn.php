<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddLiabilitySourceColumn extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'users_monthly_liability';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->unsignedTinyInteger('source')->default(0);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
}
