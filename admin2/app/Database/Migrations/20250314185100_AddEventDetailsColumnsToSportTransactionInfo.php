<?php

/**
 * ./console mig:up 20250314185100
 *
 * ./console mig:down 20250314185100
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddEventDetailsColumnsToSportTransactionInfo extends Migration
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
    public function up(): void
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->after('id')
                ->nullable();
            $table->text('event_date')
                ->after('ticket_id')
                ->comment('The date of the event to compare with the bet date')
                ->nullable();
            $table->text('event_type')
                ->after('event_date')
                ->comment('The sport category (e.g., Football, Basketball)')
                ->nullable();
            $table->text('event_description')
                ->after('event_type')
                ->comment('The specific event (e.g., Premier League, NBA Finals)')
                ->nullable();
            $table->string('bet_mode')
                ->after('event_description')
                ->comment('Live or not PreLive')
                ->nullable();
            $table->longText('event_info')
                ->after('bet_mode')
                ->comment('Full event details')
                ->nullable();

        });
    }

    /**
     * Undo the migration
     */
    public function down(): void
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropColumn('event_date');
            $table->dropColumn('event_type');
            $table->dropColumn('event_description');
            $table->dropColumn('bet_mode');
            $table->dropColumn('event_info');
        });
    }
}
