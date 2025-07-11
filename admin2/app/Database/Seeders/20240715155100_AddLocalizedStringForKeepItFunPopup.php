<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForKeepItFunPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'generic.form.validation.checkboxRequired' => "Checkbox check is required",
        ]
    ];
}
