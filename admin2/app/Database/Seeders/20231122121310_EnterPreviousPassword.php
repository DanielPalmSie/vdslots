<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class EnterPreviousPassword extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'register.old.password' => 'Current Password*',
            'register.err.password0' => 'Please enter your current password correctly.',
        ]
    ];
}
