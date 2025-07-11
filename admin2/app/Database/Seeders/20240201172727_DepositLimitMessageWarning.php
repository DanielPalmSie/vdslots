<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class DepositLimitMessageWarning extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [

        'en' => [
            'deposit_limit_warning_title' => 'Transactions are not available',
            'deposit_limit_warning_message' => 'You have reached the deposit limit for a given time period.',
            'deposit_limit_warning_reset' => 'Limit will reset on:',
        ]

    ];
}
