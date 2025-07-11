<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForJackpotSliders extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'jackpots-slider.title' => 'Top Jackpots',
            'jackpots-slider.mobile.title' => 'Jackpot Games',
            'jackpots-slider.title-link' => 'View All',
        ],
    ];
}
