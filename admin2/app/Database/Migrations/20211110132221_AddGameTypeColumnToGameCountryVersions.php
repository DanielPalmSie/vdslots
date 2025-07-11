<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddGameTypeColumnToGameCountryVersions extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'game_country_versions';
        $this->schema = $this->get('schema');
    }
    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->unsignedTinyInteger('game_type')->default(2);
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'game_type')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('game_type');
            });
        }
    }
}
