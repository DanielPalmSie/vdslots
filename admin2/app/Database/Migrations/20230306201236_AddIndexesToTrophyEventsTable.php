
<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddIndexesToTrophyEventsTable extends Migration
{
  protected $table;
  protected $index;

  public function init()
	{
		$this->table = 'trophy_events';
		$this->index = 'trophy_events_finished_index';
	}

  /**
   * Do the migration
   */
  public function up()
  {
    DB::loopNodes(function ($connection) {
        $sm = $connection->getDoctrineSchemaManager();
        $table_details = $sm->listTableDetails($this->table);
        if (!$table_details->hasIndex($this->index)) {
          $connection->statement("ALTER TABLE {$this->table} ADD INDEX {$this->index} (finished)");
        }
    }, false);
  }

  /**
   * Undo the migration
   */
  public function down()
  {
    DB::loopNodes(function ($connection) {
      $sm = $connection->getDoctrineSchemaManager();
      $table_details = $sm->listTableDetails($this->table);
      if ($table_details->hasIndex($this->index)) {
        $connection->statement("ALTER TABLE {$this->table} DROP INDEX {$this->index}");
      }
    }, false);
  }
}