<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddContentForRG80Popup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'RG80.rg.info.description.html' => 'You are one of {{top_young_losers_count}} highest losing young customers that have registered in the last {{months}} months Please take a moment to ensure you\'re playing within comfortable limits. It\'s important to keep your gaming safe and enjoyable. <br/> Consider reviewing your activity and taking a break if needed.',
            'RG80.user.comment' => 'RG80 Flag was triggered. User have a risk {{tag}}. User is one of the  top {{top_young_losers_count}} highest losing young customer that have registered in the last {{months}} months. An interaction via popup on login was made to inform customer that we noticed he a top {{top_young_losers_count}} highest losing young customer that have registered in the last {{months}} months. We recommend the player to take a break and review his limits to ensure safe and enjoyable gaming experience. '
        ]
    ];
}
