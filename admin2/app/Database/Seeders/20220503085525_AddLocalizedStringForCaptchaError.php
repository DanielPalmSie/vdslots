<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForCaptchaError extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'registration.captcha.header.text' => 'Message',
            'captcha.validation.error' => 'Wrong captcha code, please try again.',
            'registration.popup.captcha.label' => 'Enter captcha to proceed',
            'registration.captcha.cool.off' => 'Cool off period applied for',
            'registration.captcha.exceeded.attempts' => 'Too many attempts with wrong captcha'
        ]
    ];
}