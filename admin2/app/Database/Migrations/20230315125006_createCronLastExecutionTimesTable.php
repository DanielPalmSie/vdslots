<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;


class CreateCronLastExecutionTimesTable extends Migration
{

  protected $table;
  protected $schema;

  public function init()
  {
    $this->table = 'cron_last_execution_times';
    $this->schema = $this->get('schema');
  }

  /**
   * Do the migration
   */
  public function up()
  {
    $this->schema->create($this->table, function (Blueprint $table) {
      $table->asMaster();

      $table->string('cron_alias');
      $table->timestamp('executed_at')->useCurrent();

      $table->unique('cron_alias');
    });
  }

  /**
   * Undo the migration
   */
  public function down()
  {
    if ($this->schema->hasTable($this->table)) {
      $this->schema->table($this->table, function (Blueprint $table) {
        $table->asMaster();
        $this->schema->drop($this->table);
      });
    }
  }
}
