
<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class RG82DynamicContent extends SeederTranslation
{
    protected array $data = [
        "en" => [
            "RG82.rg.info.description.html" => "You are one of {{top_time_spent_customers}} " .
                "top time spent customers that have registered in the last {{days}} days.<br/>" .
                "Please take a moment to ensure you're playing within comfortable limits. " .
                "It's important to keep your gaming safe and enjoyable. " .
                "Consider reviewing your activity and taking a break if needed.",
            "RG82.user.comment" => "RG82 Flag was triggered. User has risk of {{tag}}. ".
                "User is one of the top {{top_time_spent_customers}} time spent customers that have registered " .
                "in the last {{days}} days. " .
                "An interaction via popup on login was made to inform customer that we noticed " .
                "he is a top {{top_time_spent_customers}} time spent customer that have registered " .
                "in the last {{days}} days. " .
                "We recommend the player to take a break and review his limits to ensure safe and enjoyable " .
                "gaming experience.",
        ],
    ];
}
