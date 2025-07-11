<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AmountCheckOnPnPDeposit extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'paynplay.amount.incorrect' => 'Deposit amount is incorrect',
            'paynplay.amount.lessmin' => 'Amount is less than a minimum',
            'paynplay.amount.moremax' => 'Amount is more than a maximum',
        ]
    ];
}
