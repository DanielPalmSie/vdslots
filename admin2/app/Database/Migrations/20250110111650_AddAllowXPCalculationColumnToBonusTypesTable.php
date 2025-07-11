<?php

/**
 * ./console mig:up 20250110111650
 *
 * ./console mig:down 20250110111650
 */

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Phpmig\Migration\Migration;

class AddAllowXPCalculationColumnToBonusTypesTable extends Migration
{

    protected Builder $schema;
    private string $table;
    private string $column;
    private string $default;

    public function init()
    {
        $this->table = 'bonus_types';
        $this->column = 'allow_xp_calc';
        $this->default = 0;
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    { 
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->tinyInteger($this->column)
                    ->default($this->default)
                    ->after('allow_race')
                    ->comment('0 - not calculate XP points while bonus is active, 1 - calculate XP points while user have active bonus');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn($this->column);
        });
        
    }
}
