<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForRegistrationMissingDepositLimits extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'registration.step2.missing-deposit-limits' => 'Missing deposit limits',
        ]
    ];
}
