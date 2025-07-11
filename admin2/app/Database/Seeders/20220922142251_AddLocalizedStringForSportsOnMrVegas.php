<?php
use App\Extensions\Database\Seeder\SeederTranslation;
#content pipeline test
class AddLocalizedStringForSportsOnMrVegas extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'menu.secondary.sportsbook' => 'Sports',
            'menu.secondary.sportsbook-live' => 'Live Sports',
            'mobile-secondary-top-menu-sports' => 'Sports',
            'sports-betting-history' => 'Sports History',
        ],
        'da' => [
            'menu.secondary.sportsbook' => 'Sport',
            'menu.secondary.sportsbook-live' => 'Live Sport',
            'mobile-secondary-top-menu-sports' => 'Sport',
            'sports-betting-history' => 'Sporthistorik',
        ],
        'de' => [
            'menu.secondary.sportsbook' => 'Sportarten',
            'menu.secondary.sportsbook-live' => 'Live-Sportarten',
            'mobile-secondary-top-menu-sports' => 'Sportarten',
            'sports-betting-history' => 'Sportwetten-Historie',
        ],
        'es' => [
            'menu.secondary.sportsbook' => 'Deportes',
            'menu.secondary.sportsbook-live' => 'Deportes en vivo',
            'mobile-secondary-top-menu-sports' => 'Deportes',
            'sports-betting-history' => 'Historial de deportes',
        ],
        'fi' => [
            'menu.secondary.sportsbook' => 'Urheilu',
            'menu.secondary.sportsbook-live' => 'Liveurheilu',
            'mobile-secondary-top-menu-sports' => 'Urheilu',
            'sports-betting-history' => 'Vedonlyöntihistoria',
        ],
        'hi' => [
            'menu.secondary.sportsbook' => 'खेल।',
            'menu.secondary.sportsbook-live' => 'लाइव स्पोर्ट्स।',
            'mobile-secondary-top-menu-sports' => 'खेल',
            'sports-betting-history' => 'खेल सट्टेबाजी का इतिहास।',
        ],
        'it' => [
            'menu.secondary.sportsbook' => 'Sport',
            'menu.secondary.sportsbook-live' => 'Sport in diretta',
            'mobile-secondary-top-menu-sports' => 'Sports',
            'sports-betting-history' => 'Storico Sport',
        ],
        'ja' => [
            'menu.secondary.sportsbook' => 'スポーツ',
            'menu.secondary.sportsbook-live' => 'ライブスポーツ',
            'mobile-secondary-top-menu-sports' => 'スポーツ',
            'sports-betting-history' => 'スポーツ・ベッティング履歴',
        ],
        'no' => [
            'menu.secondary.sportsbook' => 'Sport',
            'menu.secondary.sportsbook-live' => 'Live Sport',
            'mobile-secondary-top-menu-sports' => 'Sport',
            'sports-betting-history' => 'Spillhistorie',
        ],
        'pt' => [
            'menu.secondary.sportsbook' => 'Esportes',
            'menu.secondary.sportsbook-live' => 'Esportes ao Vivo',
            'mobile-secondary-top-menu-sports' => 'Esportes ',
            'sports-betting-history' => 'História do esporte',
        ],
        'sv' => [
            'menu.secondary.sportsbook' => 'Sport',
            'menu.secondary.sportsbook-live' => 'Live Sport',
            'mobile-secondary-top-menu-sports' => 'Sport',
            'sports-betting-history' => 'Sporthistorik ',
        ]
    ];
}