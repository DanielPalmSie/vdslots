<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class AddTagExtGameNameColumnsIndex extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'micro_games';
        $this->schema = $this->get('schema');
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->index(['tag', 'ext_game_name'], 'tag_ext_game_name');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropIndex( 'tag_ext_game_name');
        });
    }
}
