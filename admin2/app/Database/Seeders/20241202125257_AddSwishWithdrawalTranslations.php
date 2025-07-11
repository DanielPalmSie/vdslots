<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddSwishWithdrawalTranslations extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'withdraw.start.swish.html' => '<p>Withdrawals with Swish are processed within 5 minutes around the clock.</p>',
            'choose.swish.account' => 'Choose Swish Account',
        ]
    ];
}
