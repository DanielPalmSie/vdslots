<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class SystemVersioningTranslationsForMobile extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mobile.app.update.title' => 'Update Required',
            'mobile.app.update.message' => 'Hey there! We’ve got a fresh new version of the app ready for you. Update now to enjoy the latest features and improvements.',
            'version' => 'Version',
        ],
        'sv' => [
            'mobile.app.update.title' => 'Uppdatering krävs',
            'mobile.app.update.message' => 'Hej på er! Vi har en ny, fräsch version av appen redo för dig. Uppdatera nu för att ta del av de senaste funktionerna och förbättringarna.',
            'version' => 'Version',
        ]
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
