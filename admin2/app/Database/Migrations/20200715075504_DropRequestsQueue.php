<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class DropRequestsQueue extends Migration
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
        $this->table = 'requests_queue';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->drop();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
