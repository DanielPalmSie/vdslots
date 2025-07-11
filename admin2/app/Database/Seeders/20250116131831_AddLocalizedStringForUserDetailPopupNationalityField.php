<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForUserDetailPopupNationalityField extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'paynplay.user-details.invalid-country' => 'Please select a country'
        ]
    ];
}
