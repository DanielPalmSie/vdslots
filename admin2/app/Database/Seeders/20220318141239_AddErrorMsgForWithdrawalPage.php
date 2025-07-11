<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddErrorMsgForWithdrawalPage extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'cashier.error.required_valid_iban' => 'Please specify a valid IBAN',
        ]
    ];
}
