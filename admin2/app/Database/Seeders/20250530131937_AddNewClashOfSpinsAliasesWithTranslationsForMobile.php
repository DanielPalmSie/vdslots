<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddNewClashOfSpinsAliasesWithTranslationsForMobile extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mobile.app.all.clash.of.spins.starting.soon' => 'Starting soon',
            'mobile.app.all.clash.of.spins.winners' => 'winners',
            'mobile.app.all.clash.of.spins.minutes' => 'minutes',
        ],
        'sv' => [
            'mobile.app.all.clash.of.spins.starting.soon' => 'Börjar snart',
            'mobile.app.all.clash.of.spins.winners' => 'vinnare',
            'mobile.app.all.clash.of.spins.minutes' => 'minuter',
        ],
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
