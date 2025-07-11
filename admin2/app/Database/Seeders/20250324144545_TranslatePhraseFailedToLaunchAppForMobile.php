<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class TranslatePhraseFailedToLaunchAppForMobile extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mobile.app.fail.launch' => 'Failed to launch external application',
        ],
        'sv' => [
            'mobile.app.fail.launch' => 'Misslyckades med att starta extern applikation',
        ]
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
