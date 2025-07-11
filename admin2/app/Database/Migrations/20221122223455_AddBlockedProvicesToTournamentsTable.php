<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddBlockedProvicesToTournamentsTable extends Migration
{
	protected $table;

	protected $schema;

	public function init()
	{
		$this->table = 'tournaments';
		$this->schema = $this->get('schema');
	}

	/**
	 * Do the migration
	 */
	public function up()
	{
		$this->schema->table($this->table, function (Blueprint $table) {
			$table->string('blocked_provinces', 500);
		});
	}

	/**
	 * Undo the migration
	 */
	public function down()
	{
		$this->schema->table($this->table, function (Blueprint $table) {
			$table->dropColumn('blocked_provinces');
		});
	}
}
