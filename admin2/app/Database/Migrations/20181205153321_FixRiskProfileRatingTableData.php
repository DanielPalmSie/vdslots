<?php

use App\Extensions\Database\FManager as DB;
use App\Models\BankCountry;
use Phpmig\Migration\Migration;

class FixRiskProfileRatingTableData extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
    }


    /**
     * @throws Exception
     */
    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->whereIn('name', ['self_locked_excluded','have_deposit_and_loss_limits'])
                ->where('section', '=', 'RG')
                ->update(['type' => 'option']);
        }, true);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->whereIn('name', ['self_locked_excluded','have_deposit_and_loss_limits'])
                ->where('section', '=', 'RG')
                ->update(['type' => 'interval']);
        }, true);
    }
}
