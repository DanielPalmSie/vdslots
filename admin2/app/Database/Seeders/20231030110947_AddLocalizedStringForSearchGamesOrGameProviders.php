<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForSearchGamesOrGameProviders extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'search.games_or_providers' => 'Search Games or Game Providers'
        ]
    ];
}
