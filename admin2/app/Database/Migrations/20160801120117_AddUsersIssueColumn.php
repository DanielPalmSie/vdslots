<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddUsersIssueColumn extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'users_issues';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->bigInteger('actor_id');
            $table->index('user_id');
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('actor_id');
            $table->dropIndex('user_id');
        });
    }
}
