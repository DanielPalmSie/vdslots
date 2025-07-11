<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class AddTagToLocalizedConnections extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'localized_strings_connections';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn('tag')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->string('tag', 255);
                $table->dropUnique('target_alias_bonus_code');
                $table->unique(['target_alias', 'bonus_code', 'tag'], 'target_alias_bonus_code');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'tag')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('tag');
                $table->dropUnique('target_alias_bonus_code');
                $table->unique(['target_alias', 'bonus_code'], 'target_alias_bonus_code');
            });
        }
    }
}
