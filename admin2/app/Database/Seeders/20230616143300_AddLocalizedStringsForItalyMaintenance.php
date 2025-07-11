<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForItalyMaintenance extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'blocked.maintenance.registration.html' => '<p>Due to the ongoing maintenance, registrations on our site are unavailable between {{start_time}} and {{end_time}}</p>',
            'blocked.maintenance.login.html' => '<p>Due to the ongoing maintenance, login on our site are unavailable between {{start_time}} and {{end_time}}</p>',
        ],
        'it' => [
            'blocked.maintenance.registration.html' => '<p>A causa della manutenzione in corso, le registrazioni sul nostro sito non sono disponibili tra le {{start_time}} e le {{end_time}}</p>',
            'blocked.maintenance.login.html' => '<p>A causa della manutenzione in corso, i login sul nostro sito non sono disponibili tra le {{start_time}} e le {{end_time}}</p>',
        ]
    ];
}
