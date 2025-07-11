<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForNodeDown extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'blocked.node_down.html' => 'We are currently undergoing maintenance. Please check again in a few minutes time.',
        ]
    ];
}