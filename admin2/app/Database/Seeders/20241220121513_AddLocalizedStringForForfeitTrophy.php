<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForForfeitTrophy extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'api.user.error.trophy.not.found' => 'Trophy is not found.',
        ]
    ];
}