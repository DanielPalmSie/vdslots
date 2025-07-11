<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class CommunicationChannelVerificationPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [

        'en' => [
            'account-verification.popup-title' => 'Account Verification',
            'account-verification.enter-code' => 'Enter the 4 digit code the was sent to:',
            'account-verification.email' => 'Email:',
            'account-verification.mobile' => 'Mobile:',
            'account-verification.change-email-mobile' => 'Change email/mobile',
            'account-verification.code-placeholder' => 'Validation Code',
            'account-verification.resend-code' => 'Resend Code',
            'account-verification.validate' => 'Validate',
            'account-verification.invalid-code' => 'Invalid validation code',
        ]
    ];
}
