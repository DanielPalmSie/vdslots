<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class AddResultColumn extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'sport_transactions';
        $this->schema = $this->get('schema');
    }


    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'result')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->unsignedTinyInteger('result')->default(0);
            });
        }

        DB::loopNodes(function ($connection) {
            $connection->statement("ALTER TABLE $this->table MODIFY COLUMN bet_type enum('bet', 'win', 'void')");
        }, false);
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
