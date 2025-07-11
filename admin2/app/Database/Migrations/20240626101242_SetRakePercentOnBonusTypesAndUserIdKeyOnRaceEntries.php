<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class SetRakePercentOnBonusTypesAndUserIdKeyOnRaceEntries extends Migration
{
	private string $brand;

	public function init()
	{
		$this->brand = phive('BrandedConfig')->getBrand();
	}


	/**
	 * Do the migration
	 */
	public function up()
	{
		if ($this->brand !== 'megariches') {
			return;
		}
		
		DB::loopNodes(function ($connection) {
			$connection->statement("ALTER TABLE bonus_types CHANGE COLUMN rake_percent rake_percent int(5) NOT NULL DEFAULT '0'");
			$connection->statement("ALTER TABLE race_entries ADD INDEX user_id (user_id)");
		}, true);
	}

	/**
	 * Undo the migration
	 */
	public function down()
	{
		if ($this->brand !== 'megariches') {
			return;
		}
		
		DB::loopNodes(function ($connection) {
			$connection->statement("ALTER TABLE bonus_types CHANGE COLUMN rake_percent rake_percent int(5) NOT NULL DEFAULT '0'");
			$connection->statement("ALTER TABLE race_entries ADD INDEX user_id (user_id)");
		}, true);
	}

}
