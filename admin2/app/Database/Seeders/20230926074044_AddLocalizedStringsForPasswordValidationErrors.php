<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForPasswordValidationErrors extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'register.err.password.not.required'   => 'Password is not required for this legislation',
        ]
    ];
}
