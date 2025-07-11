<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForRestrictedRegistration extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mpreg.error.restricted' => 'This game is not allowed in your country. Please close this window and click on All Games and choose a game there.',
        ]
    ];
}
