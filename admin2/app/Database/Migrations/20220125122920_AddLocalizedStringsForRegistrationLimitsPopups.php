<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddLocalizedStringsForRegistrationLimitsPopups extends Migration
{

    private string $table = 'localized_strings';
    private Connection $connection;

    private array $new_strings = [
        [
            'alias' => 'registration.set.deposit.limit.header.text',
            'language' => 'en',
            'value' => 'Set Deposit Limit'
        ],[
            'alias' => 'registration.set.deposit.limit.description.part1',
            'language' => 'en',
            'value' => 'Before you can start your gameplay, please select your deposit limits.'
        ],[
            'alias' => 'registration.set.deposit.limit.description.part2',
            'language' => 'en',
            'value' => 'You can change or remove your deposit limit in your profile under the <a href=/?global_redirect=rg>responsible gaming</a> section.'
        ],[
            'alias' => 'registration.set.limits.text',
            'language' => 'en',
            'value' => 'Set limits'
        ],[
            'alias' => 'registration.set.login.limit.header.text',
            'language' => 'en',
            'value' => 'Set Login Limit'
        ],[
            'alias' => 'registration.login.limit.set.title',
            'language' => 'en',
            'value' => 'Set your login limit'
        ],[
            'alias' => 'registration.set.login.limit.description.part1',
            'language' => 'en',
            'value' => 'Please select your Daily, Weekly and Monthly limit.'
        ],[
            'alias' => 'registration.set.login.limit.description.part2',
            'language' => 'en',
            'value' => 'You can change your Time Limit in your profile under the <a href=/?global_redirect=rg>responsible gaming</a> section.'
        ],[
            'alias' => 'registration.set.account.balance.limit.header.text',
            'language' => 'en',
            'value' => 'Set Account Balance Limit'
        ],[
            'alias' => 'registration.set.account.balance.limit.title',
            'language' => 'en',
            'value' => 'Set your maximum account balance'
        ],[
            'alias' => 'registration.set.account.balance.limit.description.part1',
            'language' => 'en',
            'value' => 'Please select your maximum account balance. Once you reach this limit, your game play will be restricted.'
        ],[
            'alias' => 'registration.set.account.balance.limit.description.part2',
            'language' => 'en',
            'value' => 'You can change your Account Balance Limit in your profile under the <a href=/?global_redirect=rg>responsible gaming</a> section.'
        ],[
            'alias' => 'registration.account.balance.limit.description',
            'language' => 'en',
            'value' => 'Account Balance Limit'
        ],[
            'alias' => 'set.balance.limit',
            'language' => 'en',
            'value' => 'Please set balance limit.'
        ],[
            'alias' => 'time.mins.desc',
            'language' => 'en',
            'value' => 'Mins'
        ]

    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach ($this->new_strings as $string) {
            $localized_string = $this->connection->table($this->table)
                ->where('alias', '=', $string['alias'])
                ->where('language', '=', $string['language'])
                ->first();

            if (!empty($localized_string)) {
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
        foreach ($this->new_strings as $string) {
            $this->connection->table($this->table)
                ->where('alias',  '=', $string['alias'])
                ->where('language',  '=', $string['language'])
                ->delete();
        }
    }
}
