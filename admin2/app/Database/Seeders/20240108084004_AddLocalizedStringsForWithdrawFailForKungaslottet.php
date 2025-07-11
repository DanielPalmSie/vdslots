<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForWithdrawFailForKungaslottet extends SeederTranslation
{
    protected array $data = [

        'en' => [
            'paynplay.withdraw.invalidAmountError' => 'Invalid amount. Please enter a number greater than 0.',
            'paynplay.withdraw.invalidAmountPositiveError' => 'Invalid amount. Please enter a positive number.',
            'paynplay.withdraw.invalidNumericalValueError' => 'Invalid amount. Please enter a standard numerical value.'
        ]
    ];
}