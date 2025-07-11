<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddSettledAtAndExtIdToSportTransactions extends Migration
{
    /** @var string */
    protected $table;

    /** @var MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'sport_transactions';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'settled_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->timestamp('settled_at')->nullable()->after('ticket_settled');
            });
        }

        if (!$this->schema->hasColumn($this->table, 'ext_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('ext_id', 191)->nullable()->after('id');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'settled_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('settled_at');
            });
        }
        if ($this->schema->hasColumn($this->table, 'ext_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('ext_id');
            });
        }
    }
}
