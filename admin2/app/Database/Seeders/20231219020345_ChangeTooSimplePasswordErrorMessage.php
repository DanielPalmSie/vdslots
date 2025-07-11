<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class ChangeTooSimplePasswordErrorMessage extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'register.err.simple' => 'Password needs an uppercase letter, numbers, and lowercase letters.',
        ],
    ];
}
