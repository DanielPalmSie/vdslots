<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForNationalityPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'nationality.saved.success' => 'Nationality saved successfully.',
            'select.nationality.description' => 'We require you to insert the below information before you can proceed to game play.',
        ]
    ];
}
