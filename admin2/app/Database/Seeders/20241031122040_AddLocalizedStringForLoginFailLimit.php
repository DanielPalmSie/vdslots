<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForLoginFailLimit extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'blocked.login_fail_attempts.html' => '<p>Login failed, you have {{attempts}} try/tries before your account gets locked, if you forgot your password and/or username you can retrieve it <a href="/forgot-password/">here</a>.</p>'
        ]
    ];
}
