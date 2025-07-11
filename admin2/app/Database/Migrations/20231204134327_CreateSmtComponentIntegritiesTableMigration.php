<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateSmtComponentIntegritiesTableMigration extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'smt_component_integrities';
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
            $table->string('code', 1)->nullable(false);
            $table->string('name', 64)->nullable(false);
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
