<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddMitidText extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mitid.error' => 'You did not sign in correctly, please try again. Please contact our Customer Service via live chat or email <b>(support@videoslots.com)</b> if you have any further questions.',
            'mitid.login.button' => 'Log in with MIT ID',
            'mitid.login.username.field.required.error' => 'Email field is required'
        ]
    ];
}