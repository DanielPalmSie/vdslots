<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateSmtComponentCriticalitiesTableMigration extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'smt_component_criticalities';
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
            $table->timestamps(); // This will create `created_at` and `updated_at` columns with the current timestamp by default.
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
