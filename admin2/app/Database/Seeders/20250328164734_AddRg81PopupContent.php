<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddRg81PopupContent extends SeederTranslation
{
    protected array $data = [
        "en" => [
            "RG81.rg.info.description.html" => "You are one of {{top_unique_bets_customers}} unique bets customers " .
                "that have registered in the last {{days}} days.<br/>" .
                "Please take a moment to ensure you're playing within comfortable limits. " .
                "It's important to keep your gaming safe and enjoyable. " .
                "Consider reviewing your activity and taking a break if needed.<br/>",
            "RG81.user.comment" => "RG81 Flag was triggered. User has risk of {{tag}}. User is one of the top " .
                "{{top_unique_bets_customers}} unique bets customers that have registered in the last {{days}} days. " .
                "An interaction via popup on login was made to inform customer that we noticed " .
                "he is a top {{top_unique_bets_customers}} unique bets customers that have registered " .
                "in the last {{days}} days. " .
                "We recommend the player to take a break and review his limits to ensure safe and " .
                "enjoyable gaming experience.",
        ],
    ];
}
