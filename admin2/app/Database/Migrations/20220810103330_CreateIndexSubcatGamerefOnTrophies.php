<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class CreateIndexSubcatGamerefOnTrophies extends Migration
{
    private string $table = 'trophies';
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
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->index(['sub_category', 'game_ref']);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex(['sub_category', 'game_ref']);
        });
    }
}
