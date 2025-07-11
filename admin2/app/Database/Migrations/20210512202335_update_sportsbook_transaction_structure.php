<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class UpdateSportsbookTransactionStructure extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
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
        if ($this->schema->hasColumn($this->table, 'result')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('result');
            });
        }

        if ($this->schema->hasColumn($this->table, 'updated_at')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('updated_at');
            });
        }
        
        if ($this->schema->hasColumn($this->table, 'win_amount')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('win_amount');
            });
        }
        
        if ($this->schema->hasColumn($this->table, 'bet_amount')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('bet_amount');
            });
        }

        if (!$this->schema->hasColumn($this->table, 'amount')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigInteger('amount')->after('ticket_settled');
            });
        }
        
        if (!$this->schema->hasColumn($this->table, 'bet_type')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->tinyInteger('bet_type')->after('amount');
            });
        }

        if (!$this->schema->hasColumn($this->table, 'currency')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('currency', 3)->after('bet_type');
            });
        }
        
        if (!$this->schema->hasColumn($this->table, 'balance')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigInteger('balance')->after('currency');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
