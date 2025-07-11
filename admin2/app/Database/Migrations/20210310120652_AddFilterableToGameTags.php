<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddFilterableToGameTags extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'game_tags';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'filterable')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->tinyInteger('filterable')->default(0)->index();
            });
        }
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->dropIndex('alias');
            $table->unique('alias', 'alias');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'filterable')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('filterable');
            });
        }
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->dropIndex('alias');
            $table->index('alias', 'alias');
        });
    }
}
