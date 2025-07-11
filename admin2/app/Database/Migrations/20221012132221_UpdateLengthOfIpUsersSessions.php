<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use Illuminate\Database\Schema\Blueprint;


class UpdateLengthOfIpUsersSessions extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'users_sessions';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->migrateEverywhere();
            $table->useNonBlockAlter();

            $table->string('ip', 55)->change();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->migrateEverywhere();
            $table->useNonBlockAlter();

            $table->string('ip', 20)->change();
        });
    }
}