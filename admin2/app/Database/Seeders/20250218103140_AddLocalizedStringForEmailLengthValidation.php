<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForEmailLengthValidation extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'register.err.email.local.length.exceeded' => 'The local part of the email address must be 64 characters or fewer.',
        ]
    ];
}