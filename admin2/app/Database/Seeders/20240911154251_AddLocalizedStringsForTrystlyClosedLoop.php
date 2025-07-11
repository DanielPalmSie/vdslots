<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForTrystlyClosedLoop extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'invalid.selected.account' => 'The chosen account is invalid or does not exist',
            'choose.bank.account' => 'Choose bank account',
            'verify.document.popup.description' => 'Kindly proceed to the documents page to verify your new bank account prior to withdrawing',
            'verify.document.button' => 'Verify Now',
            'verify.document.popup.title' => 'Verify',
            'select.account.success.description' => 'Your bank account has successfully been added',
            'select.account.fail.description' => 'Problem adding account, select another account or contact support',
            'select.account.btn' => 'Add new bank account',
            'choose.select.bank.account.section.title' => 'Choose a bank account or select a new bank account',
            'add.bank.account.btn' => 'Add Bank Account',
            'bank.number' => 'Bank Number',
            'cashier.error.invalid.format' => 'This field does not match the format.',
            'add.bank.account.section.title' => 'Enter your bank details'
        ]
    ];
}
