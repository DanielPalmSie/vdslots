<?php

use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class UpdateSportsbookTransactionDetails extends Migration
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
            $table->bigInteger('outcome_id')->after('odds');
            $table->string('specifiers', 155)->after('outcome_id');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
