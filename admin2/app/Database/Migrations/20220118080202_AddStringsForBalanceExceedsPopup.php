<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddStringsForBalanceExceedsPopup extends Migration
{
    private $table = 'localized_strings';
    protected $connection;

    private $new_strings = [
        [
            'alias' => 'balance.limit.popup.main_title',
            'language' => 'en',
            'value' => 'Balance Limit'
        ],
        [
            'alias' => 'balance.limit.popup.reached_title',
            'language' => 'en',
            'value' => 'Maximum account balance reached.'
        ],
        [
            'alias' => 'balance.limit.popup.reached_detail',
            'language' => 'en',
            'value' => 'You have hit your maximum allowed account balance and your account has been play and deposit blocked. To remove the block, please withdraw money from your account to lower your balance.'
        ],
        [
            'alias' => 'balance.limit.popup.balance_set_title',
            'language' => 'en',
            'value' => 'Account Balance Set'
        ],
        [
            'alias' => 'balance.limit.popup.balance_set_detail',
            'language' => 'en',
            'value' => 'You are not allowed to deposit an amount ({{currency}}{{amount}}) which will exceed your Account Balance Limit.'
        ],
        [
            'alias' => 'balance.limit.popup.maximum_allowed_balance',
            'language' => 'en',
            'value' => 'Maximum allowed balance'
        ],
        [
            'alias' => 'balance.limit.popup.current_balance',
            'language' => 'en',
            'value' => 'Your current balance'
        ],
        [
            'alias' => 'balance.limit.popup.exceeded_amount',
            'language' => 'en',
            'value' => 'Exceeded amount'
        ],
        [
            'alias' => 'balance.limit.popup.withdraw_now',
            'language' => 'en',
            'value' => 'Withdraw Now'
        ],
        [
            'alias' => 'balance.limit.popup.change_limit',
            'language' => 'en',
            'value' => 'Change Limit'
        ],
    ];

    function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach($this->new_strings as $string) {
            $localized_string = $this->connection
                ->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->first();

            if(!empty($localized_string)) {
                continue;
            }

            $this->connection->table($this->table)->insert($string);
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
