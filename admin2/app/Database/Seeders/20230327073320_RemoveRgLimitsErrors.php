<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class RemoveRgLimitsErrors extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'api.user.error.rg_limits.cannot.remove' => 'You can\'t remove current limit.',
        ]
    ];
}