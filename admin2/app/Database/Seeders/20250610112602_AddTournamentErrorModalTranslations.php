<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddTournamentErrorModalTranslations extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'tournament.error.modal.body'       => 'There was an error loading your Tournament.',
            'tournament.error.modal.lobby.btn'  => 'Try Again',
            'tournament.error.modal.exit.btn'   => 'Exit',
            'tournament.error.modal.title'      => 'Tournament Error'
        ]
    ];
}
