<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;


class ModifySportsTransactionDetails extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'sport_transaction_details';
        $this->schema = $this->get('schema');
    }


    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'void_factor')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->decimal('void_factor', 8, 2)->nullable();
            });
        }

        if (!$this->schema->hasColumn($this->table, 'result')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->tinyInteger('result')->nullable();
            });
        }

        if (!$this->schema->hasColumn($this->table, 'ticket_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigInteger('ticket_id');
            });
        }
        
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'void_factor')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('void_factor');
            });
        }

        if ($this->schema->hasColumn($this->table, 'result')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('result');
            });
        }

        if ($this->schema->hasColumn($this->table, 'ticket_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('ticket_id');
            });
        }
        
    }
}
