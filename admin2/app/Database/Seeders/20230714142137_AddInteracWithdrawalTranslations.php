<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddInteracWithdrawalTranslations extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'withdraw.start.ecashout.html' => '<p>Withdraw your funds with eCashout. Your request will be processed within 5 minutes.</p>',
            'withdraw.start.interacetransfer.html' => '<p>Withdraw your funds with Interac e-Transfer. Your request will be processed within 5 minutes.</p>',
        ]
    ];
}
