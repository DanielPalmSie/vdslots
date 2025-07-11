<?php 

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForDepositCashierBankTextInfoForUk extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'deposit.start.bank.gb.html' => '<p>Deposit with your online bank account, and funds are available immediately. Withdrawals are processed instantly.</p>'
        ]
    ];
}