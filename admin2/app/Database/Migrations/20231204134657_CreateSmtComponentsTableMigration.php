<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class CreateSmtComponentsTableMigration extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'smt_components';
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
            $table->unsignedBigInteger('unique_id')->nullable(false);
			$table->unsignedBigInteger('component_category_id')->nullable();
			$table->unsignedBigInteger('subcategory_id')->nullable();
			$table->unsignedBigInteger('confidentiality_id')->nullable();
			$table->unsignedBigInteger('integrity_id')->nullable();
			$table->unsignedBigInteger('availability_id')->nullable();
			$table->unsignedBigInteger('accountability_id')->nullable();
            $table->string('name', 64)->nullable(false);
            $table->text('description')->nullable(false);
            $table->text('changes_from_previous_version')->nullable();
            $table->text('changes_on_next_version')->nullable();
            $table->text('location')->nullable();
            $table->tinyInteger('draft')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->string('serial_number', 32)->nullable();
            $table->string('version', 32)->nullable();
            $table->string('hash', 64)->nullable();
            $table->timestamp('version_valid_until')->nullable();
            $table->text('hash_path')->nullable();
            $table->string('repository', 191)->nullable();
            $table->string('repository_path', 191)->nullable();
            $table->string('branch', 191)->default('default')->nullable(false);
            $table->string('changeset', 191)->nullable();
            $table->string('db_connection', 191)->nullable();
            $table->string('db_name', 191)->nullable();
            $table->unsignedBigInteger('component_status_id')->default(1)->nullable(false);
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
