<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class ForfeitAwardsErrors extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'api.user.error.not.an.owner' => 'You are not allowed to forfeit that bonus.',
            'api.user.error.player.is.playing' => 'You cannot forfeit bonus while playing.',
            'api.user.error.bonus.is.not.active' => 'Bonus is not active.',
            'api.user.error.bonus.not.found' => 'Bonus is not found.',
        ]
    ];
}