<?php

/**
 * ./console mig:up 20250415082930
 *
 * ./console mig:down 20250415082930
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class ChangeColumnsTypesOnSportTransactionInfo extends Migration
{
    protected Builder $schema;
    private string $table;

    public function init(): void
    {
        $this->table = 'sport_transaction_info';
        $this->schema = $this->get('schema');
    }


    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dateTime('event_date')->change();
            $table->string('event_type', 50)->change();
            $table->string('event_description', 100)->change();
            $table->string('bet_mode', 7)->change();
            $table->text('event_info')->change();
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->text('event_date')->change();
            $table->text('event_type')->change();
            $table->text('event_description')->change();
            $table->string('bet_mode')->change();
            $table->longText('event_info')->change();
        });
    }
}
