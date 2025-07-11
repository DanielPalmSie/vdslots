<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForCaptcha extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'registration.popup.captcha.placeholder' => 'Insert Captcha Code', 
            'registration.popup.captcha.reset' => 'Reset', 
        ]
    ];
}