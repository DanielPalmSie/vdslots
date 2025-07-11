<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddRg79PopupContent extends SeederTranslation
{
    protected array $data = [
        "en" => [
            "RG79.rg.info.description.html" => "You are one of {{top_winning_customers}} highest winning customers " .
                "that have registered in the last {{months}} months.<br/>" .
                "Please take a moment to ensure you're playing within comfortable limits. " .
                "It's important to keep your gaming safe and enjoyable. " .
                "Consider reviewing your activity and taking a break if needed.<br/>",
            "RG79.user.comment" => "RG79 Flag was triggered. User has risk of {{tag}}. User is one of the top " .
                "{{top_winning_customers}} highest winning customers that have registered in the last {{months}} months. " .
                "An interaction via popup on login was made to inform customer that we noticed " .
                "he is a top {{top_winning_customers}} highest winning customers that have registered " .
                "in the last {{months}} months. " .
                "We recommend the player to take a break and review his limits to ensure safe and " .
                "enjoyable gaming experience.",
        ],
    ];
}
