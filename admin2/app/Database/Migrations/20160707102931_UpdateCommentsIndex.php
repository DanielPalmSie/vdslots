<?php

use Phpmig\Migration\Migration;

class UpdateCommentsIndex extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'users_comments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function ($table) {
            $table->index('user_id', 'user_id');
            $table->unsignedTinyInteger('secret');
        });
        
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function ($table) {
            $table->dropIndex('user_id');
        });
    }
}
