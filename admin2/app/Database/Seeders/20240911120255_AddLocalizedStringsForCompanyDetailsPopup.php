<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForCompanyDetailsPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'company-details-popup.title' => 'Set up account details',
            'company-details-popup.deposit-description' => 'Your account will be activated when you complete your first deposit, the below details are <b>optional</b>.',
            'company-details-popup.description' => 'The below details are <b>optional</b>.',
            'company-details-popup.citizenship' => 'Citizenship',
            'company-details-popup.citizenship.placeholder' => 'Select Citizenship',
            'company-details-popup.company-name.placeholder' => 'Company name',
            'company-details-popup.company-address.placeholder' => 'Company address',
            'company-details-popup.country-code' => 'Country code',
            'company-details-popup.company-phone-number.placeholder' => 'Company Phone number',
            'company-details-popup.error.required' => 'This field is required',
            'company-details-popup.error.invalid-data' => 'Provided data is invalid',
            'company-details-popup.skip' => 'Skip Now',
            'company-details-popup.submit' => 'Continue',
        ]
    ];
}
