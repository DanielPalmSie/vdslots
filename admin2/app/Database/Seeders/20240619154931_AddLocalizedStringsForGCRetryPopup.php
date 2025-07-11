<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForGCRetryPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'geocomply.inform.retry.limit' => "We apologize, but we couldn’t verify your location due to technical issues. Please check <span class='troubleshooter text-underline'>recommendations</span> how to fix problem. Please fix and try again in {{minutesLeft}} minute(s).",
            'geocomply.inform.retry.troubleshooter' => "We apologize, but we couldn’t verify your location due to technical issues. Please follow recommendations how to fix problem. Please fix and try again in {{minutesLeft}} minute(s).",
        ]
    ];
}
