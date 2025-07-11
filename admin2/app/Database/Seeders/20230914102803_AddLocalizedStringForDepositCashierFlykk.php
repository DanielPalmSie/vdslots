<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForDepositCashierFlykk extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'deposit.start.flykk.html' => '<p>Deposit with Flykk, your funds are immediately available.</p>'
        ]
    ];
}
