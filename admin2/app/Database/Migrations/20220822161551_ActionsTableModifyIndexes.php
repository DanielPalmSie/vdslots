<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class ActionsTableModifyIndexes extends Migration
{
    private string $table = 'actions';
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
        $this->schema->table($this->table, function(Blueprint $table)
        {
            $table->index(['actor', 'tag', 'target', 'created_at']);
            $table->index(['tag', 'target']);
            $table->index(['target', 'tag', 'created_at']);
            $table->dropIndex('actor');
            $table->dropIndex('tag');
            $table->dropIndex('target');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table)
        {
            $table->dropIndex(['actor', 'tag', 'target', 'created_at']);
            $table->dropIndex(['tag', 'target']);
            $table->dropIndex(['target', 'tag', 'created_at']);
            $table->index('actor', 'actor');
            $table->index('tag', 'tag');
            $table->index('target', 'target');
        });
    }
}
