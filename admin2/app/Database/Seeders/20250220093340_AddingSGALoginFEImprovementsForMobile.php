<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddingSGALoginFEImprovementsForMobile extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mobile.app.sign.in.issues' => 'Having issues signing in?',
            'contact.support' => 'Contact Support',
        ],
        'sv' => [
            'mobile.app.sign.in.issues' => 'Har du problem med att logga in?',
            'contact.support' => 'Kontakta support',
        ]
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
