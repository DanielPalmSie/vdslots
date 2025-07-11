<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddBalanceLimitStringsInRgPage extends Migration
{
    private $table;
    protected $connection;

    private $new_strings = [
        [
            'alias' => 'balance.limit.headline',
            'language' => 'en',
            'value' => 'Account Balance Limit'
        ],
        [
            'alias' => 'balance.limit.info.html',
            'language' => 'en',
            'value' => 'You are able to set a maximum limit to how much your account balance can be. When the limit has been reached you will get a message which informs you that your balance limit has been reached and you will not be able to make any further deposits or play until you withdraw more than the amount which exceeds this limit.
You can change your account balance limit. An increase will automatically take place after a period of 7 days. If you want to decrease your limit, the change will take place with immediate effect.'
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
