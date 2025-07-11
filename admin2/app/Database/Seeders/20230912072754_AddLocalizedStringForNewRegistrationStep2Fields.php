<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForNewRegistrationStep2Fields extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'register.main_province.not.required.message' => 'The main_province is not required.',
            'register.main_province.not.available' => 'The main_province is not available',
            'register.nationality.error.message' => 'The nationality is missing or is wrong.',
            'register.email_code.not.required.message' => 'The email code is not required.',
            'register.place_of_birth.not.required.message' => 'The place_of_birth is not required.'
        ]
    ];
}
