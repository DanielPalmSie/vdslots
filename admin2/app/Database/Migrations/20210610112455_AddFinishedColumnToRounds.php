<?php
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddFinishedColumnToRounds extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'rounds';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->boolean('is_finished')->after('win_id');
            });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'is_finished')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('is_finished');
            });
        }
    }
}
