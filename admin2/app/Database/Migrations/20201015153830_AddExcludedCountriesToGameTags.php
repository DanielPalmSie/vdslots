<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddExcludedCountriesToGameTags extends Migration
{
    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn('game_tags', 'excluded_countries')) {
            $this->schema->table('game_tags', function (Blueprint $table) {
                $table->asMaster();
                $table->string('excluded_countries', 255)
                    ->nullable(false)
                    ->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn('game_tags', 'excluded_countries')) {
            $this->schema->table('game_tags', function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('excluded_countries');
            });
        }
    }
}
