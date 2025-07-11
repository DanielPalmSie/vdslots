<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForEditProfileTermsAndConditionsSection extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'edit-profile.terms-and-conditions.title' => 'Terms & Conditions',
            'edit-profile.terms-and-conditions.btn-text' => 'Show Terms & Conditions',
        ],
    ];
}
