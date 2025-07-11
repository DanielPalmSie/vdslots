<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddValidationMessageStringInPopupLimits extends Migration
{
    private $table = 'localized_strings';
    protected $connection;

    private $new_strings = [
        [
            'alias' => 'post-registration.deposit-limit-day.invalid',
            'language' => 'en',
            'value' => 'Daily deposit limit cannot be empty and should be lower than weekly and monthly limit.'
        ],
        [
            'alias' => 'post-registration.deposit-limit-week.invalid',
            'language' => 'en',
            'value' => 'Weekly deposit limit cannot be empty and should be higher than daily limit and lower than monthly limit.'
        ],
        [
            'alias' => 'post-registration.deposit-limit-month.invalid',
            'language' => 'en',
            'value' => 'Monthly deposit limit cannot be empty and should be higher than daily or weekly limit.'
        ],
        [
            'alias' => 'post-registration.login-limit-day.invalid',
            'language' => 'en',
            'value' => 'Daily login limit cannot be empty and should be lower than weekly and monthly limit.'
        ],
        [
            'alias' => 'post-registration.login-limit-week.invalid',
            'language' => 'en',
            'value' => 'Weekly login limit cannot be empty and should be higher than daily limit and lower than monthly limit.'
        ],
        [
            'alias' => 'post-registration.login-limit-month.invalid',
            'language' => 'en',
            'value' => 'Monthly login limit cannot be empty and should be higher than daily or weekly limit.'
        ],
        [
            'alias' => 'post-registration.balance-limit.invalid',
            'language' => 'en',
            'value' => 'Balance limit cannot be empty.'
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
