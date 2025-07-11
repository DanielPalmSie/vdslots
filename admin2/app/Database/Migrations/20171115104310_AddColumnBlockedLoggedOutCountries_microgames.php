<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnBlockedLoggedOutCountriesMicrogames extends Migration
{

    protected $table;

    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'micro_games';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->string('blocked_logged_out', 256)->nullable(false)->after('blocked_countries');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('blocked_logged_out');
        });
    }

}
