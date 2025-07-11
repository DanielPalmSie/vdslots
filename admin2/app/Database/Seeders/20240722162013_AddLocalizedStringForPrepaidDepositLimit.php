<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForPrepaidDepositLimit extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'prepaid.deposit.limit.title' => 'Prepaid method deposit limit reached',
            'prepaid.deposit.limit.description' => 'You can deposit up to a total of {{max_allowed_deposit}} using a prepaid method within a rolling {{days}}-day period.',
            'prepaid.deposit.limit.total.deposit' => 'Total deposits in the last {{days}} days: ',
            'prepaid.deposit.limit.remaining.deposit' => 'Remaining allowed deposit: ',
            'prepaid.deposit.limit.max.allowed.deposit' => 'Maximum allowable deposit:',
            'prepaid.deposit.limit.btn' => 'Ok',
        ]
    ];
}
