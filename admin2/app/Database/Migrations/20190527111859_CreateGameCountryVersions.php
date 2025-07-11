<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateGameCountryVersions extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    /**
     * Do the migration
     */
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
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->bigIncrements('id');
                $table->bigInteger('game_id')->index();
                $table->string('country', 5)->index();
                $table->string('game_version', 255);
                $table->string('game_certificate_ref', 255)->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
    }
}

