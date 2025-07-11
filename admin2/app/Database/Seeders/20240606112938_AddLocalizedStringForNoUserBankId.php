<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForNoUserBankId extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'no.user.found.bankid' => 'No BankID user could be found in our system. Please register first.'
        ]
    ];
}
