<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForTrustlyWithdrawalPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'trustly.withdrawal.info.title' => 'Message',
            'withdraw.with.trustly.description' => 'Withdraw money directly to you online bank account. It is easy, fast and secure!',
            'trustly.withdraw.button' => 'Withdraw with bank',
            'other.withdraw.button' => 'Use other Methods',
        ]
    ];
}
