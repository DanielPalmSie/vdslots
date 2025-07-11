<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForEditProfileChanges extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'edit-profile.already-changed-details' => 'You can only make one change to your contact details every 30 days',
            'edit-profile.validation-code.popup-title' => 'Validation Code',
            'edit-profile.validation-code.description.html' => 'Enter the 4 digit code that was sent to:<br><br>E-mail: {{email}}<br>Mobile: {{mobile}}',
            'edit-profile.validation-code.placeholder' => 'Validation Code',
            'edit-profile.validation-code.resend-btn' => 'Resend Code',
            'edit-profile.validation-code.submit-btn' => 'Submit',
            'edit-profile.success-popup.text' => 'Your contact information has been successfully updated',
            'register.err.building.not-available' => 'The building field is not available for the requested legislation',
            'register.calling-code' => 'Mobile country code',
            'register.err.calling-code.unknown' => 'Unknown mobile country code',
        ]
    ];
}
