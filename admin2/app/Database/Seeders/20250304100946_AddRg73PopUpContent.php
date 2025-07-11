<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddRg73PopUpContent extends SeederTranslation
{
    protected array $data = [
        'en' => [
            "RG73.rg.info.description.html" => "You've played for {{number_of_hours_played}} hours in the last " .
                "{{hours_duration}} hours.<br/>It's important to take regular breaks to ensure you're playing within " .
                "comfortable limits. Please consider taking a pause to reflect on your gaming activity.<br/>",
            "RG73.user.comment" => "RG73 Flag was triggered. User has a risk of {{tag}}. " .
                "User played {{number_of_hours_played}} " .
                "number of hours during last {{hours_duration}} hours. An interaction via popup in gameplay was " .
                "made to inform customer that we noticed that the user has played {{number_of_hours_played}} hours " .
                "in the last {{hours_duration}} hours. We recommend the player to take a break " .
                "and reflect on the level of gaming activity.",
        ],
    ];
}
