<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddRealityCheckMsgElapsedtimeMT extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'reality-check.msg.elapsedtime.www' => "You have requested a Reality Check every {{minutes}} minute(s) of gameplay.
            Your gaming session has now reached {{minutes_reached}} minute(s).<br>Winnings: {{win}}<br>Losses: {{loss}}<br>Overall: {{currency}} {{winloss}}",
        ],
    ];
}