<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddExcludedIncludedCountriesToMenus extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'menus';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'excluded_countries')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->string('included_countries', 250);
                $table->string('excluded_countries', 250);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'excluded_countries')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('included_countries');
                $table->dropColumn('excluded_countries');
            });
        }
    }
}
