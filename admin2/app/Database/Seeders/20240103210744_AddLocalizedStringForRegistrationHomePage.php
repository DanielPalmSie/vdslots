<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForRegistrationHomePage extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'register.email.nostar' => 'Email'
        ]
    ];
}