<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForRegistrationForbiddenError extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'register.err.forbidden' => 'Registration is forbidden',
        ],
        'da' => [
            'register.err.forbidden' => 'Registrering er forbudt',
        ],
    ];
}
