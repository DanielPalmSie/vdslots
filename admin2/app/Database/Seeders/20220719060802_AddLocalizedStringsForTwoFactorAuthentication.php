<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForTwoFactorAuthentication extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'security.headline' => 'Security',
            'two.factor.authentication.info' => 'Two-factor authentication is an advanced security measure. Once enabled, you will be required to use a validation code to login. You will receive your validation code via SMS and email.',
            'enable.two.factor.authentication.check' => 'Enable 2-factor authentication',
            'two.factor.authentication.header' => 'Two-factor authentication',
            'two.factor.authentication.description' => 'Enter 4 digit validation code that was sent via SMS/Email',
            'validate' => 'Validate',
            'enter.code' => 'Enter code here',
            'auth.error.empty' => 'Please insert the code you received on your sms/email',
            'auth.error.description' => 'Incorrect code please try again',
        ]
    ];
}