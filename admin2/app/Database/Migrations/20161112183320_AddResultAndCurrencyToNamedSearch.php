<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddResultAndCurrencyToNamedSearch extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'named_searches';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->bigInteger('result')->nullable();
            $table->string('currency', 3)->nullable();
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('result');
            $table->dropColumn('currency');
        });
    }
}