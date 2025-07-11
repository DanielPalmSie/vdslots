<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddIconToMenusTable extends Migration
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
        if (!$this->schema->hasColumn($this->table, 'icon')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->string('icon', 255)->after('excluded_countries');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'icon')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('icon');
            });
        }
    }
}
