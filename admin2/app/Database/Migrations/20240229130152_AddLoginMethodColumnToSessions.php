<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddLoginMethodColumnToSessions extends Migration
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
        if (!$this->schema->hasColumn($this->table, 'login_method')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->string('login_method', 50);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'login_method')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('login_method');
            });
        }
    }
}
