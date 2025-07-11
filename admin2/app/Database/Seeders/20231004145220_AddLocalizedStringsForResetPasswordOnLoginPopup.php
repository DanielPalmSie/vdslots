<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForResetPasswordOnLoginPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'login.reset-password.description' => 'Kindly update your password for enhanced security',
            'login.reset-password.password-input.placeholder' => 'New password',
            'login.reset-password.password-confirmation-input.placeholder' => 'New password again',
            'login.reset-password.btn' => 'Reset password and login',
            'login.reset-password.error.used-previously' => "This password has been used previously. Please create a new one.",
        ],
    ];
}
