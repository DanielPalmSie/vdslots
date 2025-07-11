<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForLoginMethodNotAllowed extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'blocked.login_method_not_allowed.html' => 'Login method is not allowed',
        ]
    ];
}
