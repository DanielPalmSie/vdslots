<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddGenericMessagesOnIncorrectLogin extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'register.err.email.error.update' => 'Error in updating information'
        ]
    ];
}
