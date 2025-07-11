<?php 

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForBirthCountryRegistrationField extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'register.birth_country.error.message' => 'Wrong birth country',
        ],
    ];
}