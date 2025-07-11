<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class TranslateInvalidCountryPrefix extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'register.err.country_prefix' => 'Invalid country prefix',
        ]
    ];
}