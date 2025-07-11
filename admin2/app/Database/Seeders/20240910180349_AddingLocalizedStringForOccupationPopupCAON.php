<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddingLocalizedStringForOccupationPopupCAON extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'occupational.form.validation.emptyIndustry' => 'Please select a Industry',
            'occupational.popup.industry.title' => 'Industry'
        ]
    ];
}
