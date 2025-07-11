<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAnalyticsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'analytics';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->bigIncrements('id');
                $table->string('model', 20)->nullable(false);
                $table->bigInteger('model_id')->nullable(false)->index();
                $table->bigInteger('user_id')->nullable(false)->index();
                $table->string('slug', 50)->nullable(false);
                $table->string('browser', 20)->nullable();
                $table->string('user_agent', 100)->nullable();
                $table->string('device', 20)->nullable();
                $table->string('traffic_source', 5)->nullable();
                $table->string('ga_cookie_id', 20)->nullable();
                $table->dateTime('created_at');
                $table->timestamp('updated_at');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->dropIndex('analytics_model_id_index');
                $table->dropIndex('analytics_user_id_index');
                $table->drop();
            });
        }
    }
}
