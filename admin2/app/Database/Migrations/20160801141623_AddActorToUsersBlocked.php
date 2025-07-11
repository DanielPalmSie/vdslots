<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddActorToUsersBlocked extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'users_blocked';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->bigInteger('actor_id');
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('actor_id');
        });
    }
}
