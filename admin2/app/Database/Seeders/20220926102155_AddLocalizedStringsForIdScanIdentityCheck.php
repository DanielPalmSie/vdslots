<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForIdScanIdentityCheck extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'idscan.identity.title' => 'Verify your Identity',
            'idscan.identity.description' => 'To Proceed with Registration, please verify your identity using the ID Scan tool.',
            'idscan.identity.qrscan.description' => 'Scan the QR code to proceed on your smartphone.',
            'idscan.upload.button.document' => 'Manually Upload Documents',
            'idscan.identity.verification_failed' => 'Your identity couldn\'t be verified',
            'idscan.identity.verification_failed_description' => 'Unfortunetely you can\'t proceed with a registration.',
            'idscan.upload.button.close' => 'Close',
            'idscan.upload.button.Continue' => 'Continue',
            'idscan.upload.button.Cancel' => 'Cancel',
            'idscan.identity.verification_success' => 'Your identity was successfully verified',
            'idscan.identity.verification_success_description' => 'you can now proceed with registration.',
            'idscan.prodvider_title_gateway' => 'Provide some identification',
            'idscan.prodvider_title_liveness' => 'Match the identification to your face',
            'idscan.prodvider_title_results' => 'Results. Updated',
            'idscan.prodvider_title_login'   => 'Log in',
            'idscan.result_new_journey' => 'Upload another ID document',
            'idscan.result_finish' => 'Finish',
            'idscan.provider_titile_smart_capture' => 'Scan your identification',
            'idscan.login_submit' => 'Log in',
            'idscan.upload_photo' => 'Upload a photo',
            'idscan.auto_capture' => 'Auto Capture',
            'idscan.capture_photo' => 'Capture Photo',
        ]
    ];
}
