<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddJackpotCountryFilterColumns extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'jackpots';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('excluded_countries');
            $table->index('excluded_countries');
            $table->string('included_countries');
            $table->index('included_countries');
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('excluded_countries');
            $table->dropColumn('included_countries');            
        });
    }
}
