<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddErrorMessageForUnamePwdLimitAttempts extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'blocked.uname-pwd.toomanyattempts.html' => 'Too many login attempts. Please try again later.',
        ],
    ];
}
