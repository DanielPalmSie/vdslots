<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateValueTransactionsTable extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;
    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'value_transactions';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('user_id')->index();
                $table->bigInteger('session_id')->index();
                $table->bigInteger('entry_id')->index();
                $table->bigInteger('award_id')->index();
                $table->bigInteger('giver_id')->index();
                $table->bigInteger('amount');
                $table->string('source', 25)->index();
                $table->string('currency', 3)->index();                
                $table->string('description', 125);                
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
    }
}
