<?php

use App\Extensions\Database\Seeder\SeederTranslation;

/**
 * ./console seeder:up 20250212203734
 *
 * ./console seeder:down 20250212203734
 */
class AddLocalizedStringForEmptyAwards extends SeederTranslation
{

    protected array $data = [
        'en' => [
            'no.rewards.found' => 'No Rewards Found',
        ]
    ];

}
