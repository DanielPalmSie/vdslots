<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class UpdateResponsibilityCheck extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'responsibility_check';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasColumn($this->table, 'status')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        if ($this->schema->hasColumn($this->table, 'solution_provider')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->dropColumn('solution_provider');
            });
        }

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->string('status', 20);
        });

        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->enum('solution_provider', ['GBG','BeBettor']);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
