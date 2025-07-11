<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsErrorMessagesToValidator extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'invalid.swift.bic.length' => 'Invalid SWIFT BIC length. It should be 8 or 11 characters long.',
            'invalid.swift.bic.format' => 'Invalid SWIFT BIC format. It follows a format that identifies your bank, country, location, and branch.',
            'invalid.bank.name'        => 'Invalid bank name.',
            'invalid.branch.code.length.11' => 'Branch code must be 11 characters.',
            'invalid.currency'         => 'Invalid currency.'
        ]
    ];
}
