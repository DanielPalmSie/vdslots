<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class AddWinsIndex extends Migration
{

    private string $table = 'wins';
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function(Blueprint $table){
            $table->index(['user_id', 'created_at']);
            $table->dropIndex('user_id'); //dropping by name because it was created manually
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function(Blueprint $table){
            $table->index(['user_id'], 'user_id'); //restoring previous name
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
}
