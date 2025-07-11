<?php

use Phpmig\Migration\Migration;
use \App\Extensions\Database\Schema\MysqlBuilder;
use App\Extensions\Database\Schema\Blueprint;

class AddGamerefTypeCategoryToTrophiesTable extends Migration
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
            $table->index(['game_ref', 'type', 'category'], 'gameref_type_category');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex('gameref_type_category');
        });
    }
}
