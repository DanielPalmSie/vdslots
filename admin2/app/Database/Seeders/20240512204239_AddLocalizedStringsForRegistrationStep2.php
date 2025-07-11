<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForRegistrationStep2 extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'register.custom.error.message' => 'Must be 1-50 characters. Use letters, accented characters, apostrophes, hyphens, and spaces. Avoid special characters at the beginning.',
            'register.address.error.message' => 'Address must be 3-100 characters. Use letters, numbers, accented characters, commas, periods, hyphens, apostrophes, spaces, slashes, ampersands, and pound signs. Avoid special characters at the beginning.',
            'register.zipcode.error.message' => 'Zip code must be 3-20 characters. Use letters, numbers, hyphens, and spaces. Avoid special characters at the beginning.'
        ]
    ];
}