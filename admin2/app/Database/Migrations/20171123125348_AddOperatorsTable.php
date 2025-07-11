<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddOperatorsTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'operators';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->increments('id');
            $table->char('name', 25)->index();
            $table->char('network', 25);
            $table->string('blocked_countries', 500);
            $table->string('blocked_countries_non_branded', 500);
            $table->string('blocked_countries_jackpot', 500);
            $table->float('branded_op_fee', 10, 2);
            $table->float('non_branded_op_fee', 10, 2);
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
