<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForInActivePlayerEmail extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'mail.inactive_players_email.subject' => 'Cleared Player Balance',
            'mail.inactive_players_email.content' => 'Find attached document for inactive players',
        ]
    ];
}
