<?php

use Phpmig\Migration\Migration;

class AddInterventionTypeAndCauseToUserComments extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'users_comments';
        $this->schema = $this->get('schema');
    }
    
    /**
     * Do the migration
     */
    public function up()
    {
      $this->schema->table($this->table, function ($table) {
            $table->string('intervention_type')->nullable();
            $table->string('intervention_cause')->nullable();
      });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
      $this->schema->table($this->table, function ($table) {
            $table->dropColumn('intervention_type');
            $table->dropColumn('intervention_cause');
      });

    }
}