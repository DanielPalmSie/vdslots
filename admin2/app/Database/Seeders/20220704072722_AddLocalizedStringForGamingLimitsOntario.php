<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForGamingLimitsOntario extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'gaming.limit.popup.title' => 'Set Gaming Limits',
            'gaming.limit.confirmation.description' => 'Do you wish to set your gaming limits ?',
            'gaming.set.limit.description' => 'Set your gaming limits here. You can change or remove your limits in your profile under the responsible gaming section.',
            'gaming.set.limit.mobile.title' => 'Set your limits'
        ]
    ];
}