<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class UsersAlreadyRegisteredErrorTranslationForMobile extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mobile.app.user.already.registered' => 'The user is already registered',
        ],
        'sv' => [
            'mobile.app.user.already.registered' => 'Användaren är redan registrerad',
        ]
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
