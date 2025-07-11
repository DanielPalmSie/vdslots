<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddUniqueIndexToGameTagCon extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'game_tag_con';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->unique(['game_id', 'tag_id'], 'game_id_tag_id');
            $table->dropIndex('game_id'); // the unique index will be used instead having game_id defined as first column
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropUnique('game_id_tag_id');
            $table->index('game_id', 'game_id');
        });
    }
}
