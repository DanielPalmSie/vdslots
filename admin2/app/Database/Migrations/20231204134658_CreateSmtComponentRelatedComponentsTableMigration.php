<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateSmtComponentRelatedComponentsTableMigration extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'smt_component_related_components';
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
            $table->unsignedBigInteger('parent_component_id');
            $table->unsignedBigInteger('child_component_id');
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
