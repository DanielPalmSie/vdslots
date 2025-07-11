<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class UpdateSportsbookTransactionDetailsOutcomeId extends Migration
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
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->string('outcome_id')->change();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->bigInteger('outcome_id')->change();
        });
    }
}
