<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForGetCategoriesWithGamesApi extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'api.category.last.played.name' => 'Last Played',
            'api.category.featured.cgames.name' => 'Featured Games',
            'api.category.videoslots.name' => 'Video Slots',
            'api.category.videoslots_jackpot.name' => 'Jackpot Games',
            'api.category.live-casino.name' => 'Live Casino',
            'api.category.table-games.name' => 'Table Games',
            'api.category.videopoker.name' => 'Video Poker',
            'api.category.arcade.name' => 'Arcade',
            'api.category.other.name' => 'Other Games',
            'api.category.scratch-cards.name' => 'Scratch Cards',
        ]
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
