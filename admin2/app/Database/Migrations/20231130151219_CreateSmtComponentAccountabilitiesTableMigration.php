<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateSmtComponentAccountabilitiesTableMigration extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'smt_component_accountabilities';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->create($this->table, function (Blueprint $table) {

            $table->asMaster();

            $table->id();
            $table->string('code', 1);
            $table->string('name', 64);
            $table->timestamps();
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
