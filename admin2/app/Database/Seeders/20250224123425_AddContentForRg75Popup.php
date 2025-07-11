<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddContentForRg75Popup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'RG75.rg.info.description.html' => '
You\'ve wagered {{total_wager}} in the last {{duration}} hours. <br/>
Please take a moment to ensure you\'re playing within comfortable limits. It\'s important to keep your gaming safe and enjoyable. Consider reviewing your activity and taking a break if needed. <br/>',
            'RG75.user.comment' => '
RG75 Flag was triggered. User have a risk {{tag}}. User wagered {{total_wager}} amount in the last {{duration}} hours.
An interaction via popup in gameplay was made to inform customer that we noticed he wagered {{total_wager}} amount in the last {{duration}} hours.
We recommend the player to take a break and review his limits to ensure safe and enjoyable gaming experience.'
        ]
    ];
}
