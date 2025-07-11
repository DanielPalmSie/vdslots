<?php

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddExcludedBrandsColumnInMenusTable extends Migration
{
    protected string $table;
    protected Builder $schema;
    protected string $column;

    public function init()
    {
        $this->table = 'menus';
        $this->schema = $this->get('schema');
        $this->column = 'excluded_brands';
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
                ->after('excluded_provinces');
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
