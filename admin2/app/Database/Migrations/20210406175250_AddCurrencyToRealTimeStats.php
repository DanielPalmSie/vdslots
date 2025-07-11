<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddCurrencyToRealTimeStats extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'users_realtime_stats';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {

        if (!$this->schema->hasColumn($this->table, 'currency')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('currency', 3)->default('EUR')->after('user_id');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'currency')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('currency');
            });
        }
    }
}
