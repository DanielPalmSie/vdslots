<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Illuminate\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddSeederTable extends Migration
{
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table =  getenv('DATABASE_SEEDERS_TABLE') ?: 'seeders_backoffice';
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
                $table->string('version');
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
