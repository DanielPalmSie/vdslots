<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForPrivacyDashboard extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'notification' => 'Notification',
            'register.opt.in.promotions' => 'I wish to receive all kind of free spins and promotional offers via all channels (e-mail, SMS, phone, post and notifications).'
        ]
    ];
}