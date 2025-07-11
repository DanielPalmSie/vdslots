<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForNzInpayWithdrawal extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'bank.bank_clearing_bsb' => 'BSB:',
            'invalid.bank_clearing_bsb' => 'Please specify a valid Bank BSB.',
        ]
    ];
}
