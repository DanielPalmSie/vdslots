<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class GameThemes extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'game_themes';
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->increments('id');
            $table->unsignedBigInteger('game_id')->index();
            $table->unsignedInteger('theme_id')->index();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
