<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class AutoActivateBonusOnVsAndMRVMigration extends Migration
{
	private $schema;
	private $tables;
	private $table;

	public function init()
	{
		$this->schema = $this->get('schema');
		$this->tables = ['bonus_types', 'bonus_entries'];
		$this->table = 'welcome_bonus_trophies';
	}
	
	
    /**
     * Do the migration
     */
    public function up()
    {		
		foreach ($this->tables as $tableName) {
			if (!$this->schema->hasColumn($tableName, 'auto_activate_bonus_id')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->integer('auto_activate_bonus_id')->nullable();
				});
			}

			if (!$this->schema->hasColumn($tableName, 'auto_activate_bonus_day')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->tinyInteger('auto_activate_bonus_day')->nullable();
				});
			}

			if (!$this->schema->hasColumn($tableName, 'auto_activate_bonus_period')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->tinyInteger('auto_activate_bonus_period')->nullable();
				});
			}

			if (!$this->schema->hasColumn($tableName, 'auto_activate_bonus_send_out_time')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->time('auto_activate_bonus_send_out_time')->nullable();
				});
			}
		}

		try {
			$this->schema->create($this->table, function (Blueprint $table) {
				$table->asSharded();
				$table->bigIncrements('id');
				$table->unsignedBigInteger('user_id');
				$table->bigInteger('welcome_bonus_entry_id');
				$table->bigInteger('bonus_entry_id')->nullable();
				$table->tinyInteger('step');
				$table->enum('status', ['pending', 'awarded', 'active', 'completed', 'failed'])->default('pending');
				$table->timestamp('execute_at')->useCurrent();
				$table->timestamp('created_at')->useCurrent();
				$table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
			});

            DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                $connection->statement("ALTER TABLE welcome_bonus_trophies MODIFY COLUMN user_id BIGINT(21) UNSIGNED NOT NULL;");
            }, true);
		} catch (\Exception $e) {
			// If error is about table already existing, we can continue
			if (strpos($e->getMessage(), 'Base table or view already exists') === false) {
				throw $e;
			}
		}
    }

    /**
     * Undo the migration
     */
    public function down()
    {
		foreach ($this->tables as $tableName) {
			if ($this->schema->hasColumn($tableName, 'auto_activate_bonus_id')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->dropColumn('auto_activate_bonus_id');
				});
			}
			
			if ($this->schema->hasColumn($tableName, 'auto_activate_bonus_day')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->dropColumn('auto_activate_bonus_day');
				});
			}
			
			if ($this->schema->hasColumn($tableName, 'auto_activate_bonus_period')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->dropColumn('auto_activate_bonus_period');
				});
			}
			
			if ($this->schema->hasColumn($tableName, 'auto_activate_bonus_send_out_time')) {
				$this->schema->table($tableName, function (Blueprint $table) {
					$table->migrateEverywhere();
					$table->dropColumn('auto_activate_bonus_send_out_time');
				});
			}
		}
		
		if ($this->schema->hasTable($this->table)) {
			$this->schema->table($this->table, function (Blueprint $table) {
				$table->asSharded();
				$table->drop();
			});
		}
    }
}
