<?php 

use App\Extensions\Database\Seeder\SeederTranslation;

class AddBlockAccountErrors extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'api.user.error.rg.block.account.min_1d' => 'The num_days value should be at least 1 day',
            'api.user.error.rg.block.account.min_24h' => 'The num_hours value should be at least 24',
            'api.user.error.rg.block.account.x24' => 'The num_hours value should be a multiple of 24.',
        ]
    ];
}