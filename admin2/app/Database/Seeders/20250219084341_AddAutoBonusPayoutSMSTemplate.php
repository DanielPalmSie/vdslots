<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddAutoBonusPayoutSMSTemplate extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'bonus.auto.payout.sms' => 'Hey {{user.username}}, your 25 Free Spins for Ave Caesar Dynamic Ways are ready! Log in at the Royal Court of Kungaslottet and claim your reward!',
        ]
    ];
}
