<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForWithdrawFormAndValidators extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'invalid.account.number'   => 'Please specify a valid Account Number.',
            'invalid.bank_clearing_system_id'   => 'Please specify a valid Bank Clearing System ID.',
            'invalid.zipcode'   => 'Please specify a valid Zip Code.',
            'invalid.nid'  => 'Please specify a valid National ID Number.',
            'email.is.required'   => 'Email is required.',
            'nid.is.required'   => 'National ID Number is required.',
            'province.is.required'   => 'Province is required.',
            'bank.bank_account_number'   => 'Account Number:',
            'bank.bank_clearing_system_id'   => 'Bank Clearing System ID:',
        ]
    ];
}
