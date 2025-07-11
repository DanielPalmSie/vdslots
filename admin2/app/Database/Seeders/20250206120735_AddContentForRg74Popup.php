<?php
use App\Extensions\Database\Seeder\SeederTranslation;
class AddContentForRg74Popup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'RG74.rg.info.description.html' => '
                        You\'ve wagered {{amount_of_spins}} spins in the last {{hours_period}} hours. <br/>
It\'s important to take breaks and ensure you\'re playing within comfortable limits. Consider taking a moment to reflect on your activity and maintain a balanced gaming experience. <br/>',
            'RG74.user.comment' => 'RG74 Flag was triggered. User has a risk of {{tag}}. User played {{amount_of_spins}} spins in the last {{hours_period}} hours. An interaction via popup in gameplay was made to inform customer that we noticed he wagered {{amount_of_spins}} spins in the last {{hours_period}} hours. We recommend the player to take a break and balance his gaming activity.'
        ]
    ];
}
