<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForFitForPlayCheck extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [

        'en' => [
            'fit_for_play_info' => 'By continuing i confirm i am fit to play',
            'fit_for_play_description' => 'This means you acknowledge that you are not currently under the influence of alcohol or any other substances that may hinder your ability to exercise sound judgement',
        ]

    ];
}
