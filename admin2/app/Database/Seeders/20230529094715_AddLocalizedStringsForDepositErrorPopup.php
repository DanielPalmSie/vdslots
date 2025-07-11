<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForDepositErrorPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'trustly.deposit.problem'       => 'Problem with your deposit?',
            'trustly.deposit.description'   => 'Ditch the hassle - Trustly makes deposits a breeze!',
            'trustly.deposit.button'        => 'Deposit with Trustly',
            'try.again'                     => 'Try Again',
            'trustly.deposit.info.title'    => 'Message',
            'continue.with.paypal'          => 'Continue to Paypal',
            'deposit.with.trustly.description'  => 'Enhance your deposit experience with <b>Trustly</b> - the Card Alternative'
        ]
    ];
}