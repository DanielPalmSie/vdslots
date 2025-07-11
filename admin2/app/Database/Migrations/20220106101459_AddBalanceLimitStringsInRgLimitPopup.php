<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddBalanceLimitStringsInRgLimitPopup extends Migration
{
    private $table;
    protected $connection;

    private $new_strings = [
        [
            'alias' => 'rg.info.balance.limits',
            'language' => 'en',
            'value' => 'Balance Limit'
        ],
        [
            'alias' => 'rg.info.popup.winloss.period.NL',
            'language' => 'en',
            'value' => 'Last 12 months'
        ],
    ];

    function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach($this->new_strings as $string) {
            $this->connection
                ->table($this->table)
                ->insert($string);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach($this->new_strings as $string) {
            $this->connection
                ->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->delete();
        }
    }
}
