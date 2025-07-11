<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class AddBetsIndex extends Migration
{

    private string $table = 'bets';
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
            $table->dropIndex('userid_idx'); //dropping by name because it was created manually
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function(Blueprint $table){
            $table->index(['user_id'], 'userid_idx'); //restoring previous name
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
}
