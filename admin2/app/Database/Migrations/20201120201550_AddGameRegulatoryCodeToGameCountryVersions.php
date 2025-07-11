<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddGameRegulatoryCodeToGameCountryVersions extends Migration
{
    /**
     * @var \App\Extensions\Database\Schema\MysqlBuilder
     */
    protected $schema;

    /**
     * @var
     */
    protected $table;

    /**
     * Do the migration
     */
    public function init()
    {
        $this->schema = $this->get('schema');
        $this->table = 'game_country_versions';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'game_regulatory_code') || !$this->schema->hasColumn($this->table, 'ext_game_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->string('game_regulatory_code', 255)
                    ->nullable(true);
                $table->string('ext_game_id', 70)->after('game_id')
                    ->nullable(true);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'game_regulatory_code') ) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('game_regulatory_code');
            });
        }
         if ($this->schema->hasColumn($this->table, 'ext_game_id') ) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('ext_game_id');
            });
        }
    }
}
