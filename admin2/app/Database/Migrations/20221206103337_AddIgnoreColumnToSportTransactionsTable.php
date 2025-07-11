<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddIgnoreColumnToSportTransactionsTable extends Migration
{
    public function init()
    {
        $this->table = 'sport_transactions';
        $this->schema = $this->get('schema');
        $this->column = 'ignore_sportsbook_history';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->tinyInteger($this->column)->nullable();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asSharded();
            $table->dropColumn($this->column);
        });
    }
}
