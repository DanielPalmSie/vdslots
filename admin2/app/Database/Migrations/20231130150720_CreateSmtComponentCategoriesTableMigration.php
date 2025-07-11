<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateSmtComponentCategoriesTableMigration extends Migration
{

    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'smt_component_categories';
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
            $table->string('name', 64)->nullable(false);
            $table->string('type', 64)->default('default')->nullable(false);
            $table->string('subtype', 64)->default('default')->nullable(false);
            $table->unsignedInteger('_lft')->default(0)->nullable(false);
            $table->unsignedInteger('_rgt')->default(0)->nullable(false);
            $table->unsignedInteger('parent_id')->nullable();
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
