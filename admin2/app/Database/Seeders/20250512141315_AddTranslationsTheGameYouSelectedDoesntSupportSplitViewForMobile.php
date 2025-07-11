<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddTranslationsTheGameYouSelectedDoesntSupportSplitViewForMobile extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'mobile.app.unsupported-splitview.game' => 'The game you\'ve selected doesn’t support Split View. Please switch to full screen to play, or choose a different game that supports this mode.',
        ],
        'sv' => [
            'mobile.app.unsupported-splitview.game' => 'Spelet du har valt stöder inte delad vy. Växla till helskärm för att spela, eller välj ett annat spel som stöder det här läget.',
        ]
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
