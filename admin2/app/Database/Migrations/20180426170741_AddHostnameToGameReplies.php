<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddHostnameToGameReplies extends Migration
{

    protected $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        $this->schema->table('game_replies', function (Blueprint $table) {
            $table->asMaster();
            $table->string('host', 50);
        });

        $this->schema->table('slow_game_replies', function (Blueprint $table) {
            $table->asMaster();
            $table->string('host', 50);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table('game_replies', function (Blueprint $table) {
            $table->asMaster();
            $table->dropColumn('host');
        });

        $this->schema->table('slow_game_replies', function (Blueprint $table) {
            $table->asMaster();
            $table->string('host');
        });
    }
}
