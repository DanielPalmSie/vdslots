<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddExcludedProvincesColumnToMenusTable extends Migration
{
    protected string $table;
    protected \App\Extensions\Database\Schema\MysqlBuilder $schema;
    protected string $column;

    public function init()
    {
        $this->table = 'menus';
        $this->schema = $this->get('schema');
        $this->column = 'excluded_provinces';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string($this->column)
                ->nullable(true)
                ->after('excluded_countries');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->dropColumn($this->column);
        });
    }
}
