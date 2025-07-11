<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForPaypalToTrustlyPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'trustly.deposit.info.title' => 'Message',
            'trustly.deposit.button' => 'Deposit with Trustly',
            'continue.with.paypal' => 'Continue with Paypal',
            'deposit.with.trustly.description' => 'Enhance your deposit experience with <b>Trustly</b> - the card alternative.',
        ]
    ];
}
