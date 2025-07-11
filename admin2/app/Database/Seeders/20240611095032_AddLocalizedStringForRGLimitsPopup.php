<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForRGLimitsPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'rg.popup.no.limits.yet' => "You don't have limits yet",
            'rg.info.edit.boundaries' => "Change your Boundaries",
            'show.total' => "Show total.",
        ]
    ];
}
