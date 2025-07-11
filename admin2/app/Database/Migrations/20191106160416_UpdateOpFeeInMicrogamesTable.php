<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;


class UpdateOpFeeInMicrogamesTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'micro_games';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
            $connection->statement("ALTER TABLE micro_games ALTER op_fee SET DEFAULT 0.15;");
        }, true);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
            $connection->statement("ALTER TABLE micro_games ALTER op_fee SET DEFAULT 0.125;");
        }, true);
    }
}
