<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddExtraColumnToLimits extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'rg_limits';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'extra')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('extra')->after('type');
                $table->dropIndex('user_id');
                $table->unique(['user_id', 'type', 'time_span', 'extra'], 'user_id');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'extra')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('extra');
                $table->dropIndex('user_id');
                $table->unique(['user_id', 'type', 'time_span'], 'user_id');
            });
        }
    }
}
