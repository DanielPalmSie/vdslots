<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddExcludedCountriesToTournamentsTable extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'tournaments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'reg_lim_excluded_countries')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->string('reg_lim_excluded_countries', 100);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'reg_lim_excluded_countries')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->dropColumn('reg_lim_excluded_countries');
            });
        }
    }
}
