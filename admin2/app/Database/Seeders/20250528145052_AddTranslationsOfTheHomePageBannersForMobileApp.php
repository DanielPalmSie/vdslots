<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddTranslationsOfTheHomePageBannersForMobileApp extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mobile.app.game.of.week' => 'Game of the week',
            'mobile.app.live.casino.spotlight' => 'Live Casino Spotlight',
        ],
        'sv' => [
            'mobile.app.game.of.week' => 'Veckans match',
            'mobile.app.live.casino.spotlight' => 'Live Casino i Rampljuset',
        ],
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
