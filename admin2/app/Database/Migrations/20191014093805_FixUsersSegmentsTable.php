<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class FixUsersSegmentsTable extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'users_segments';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'ended_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigInteger('segment_id');
                $table->bigInteger('group_id');
                $table->timestamp('started_at');
                $table->timestamp('ended_at');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'ended_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('segment_id');
                $table->dropColumn('group_id');
                $table->dropColumn('started_at');
                $table->dropColumn('ended_at');
            });
        }
    }
}
