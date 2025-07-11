<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class UpdatePayNPlayDepositPopupStrings extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'paynplay.deposit.failure.btn'   => 'OK',
            'paynplay.deposit.description'   => 'By clicking Deposit & Play you accept our <b> <a href="/privacy-policy/">Privacy Notice</a></b> and <b> <a href="/terms-and-conditions/">Terms & Conditions.</a></b>',
            'paynplay.deposit.description.mobile'  => 'By clicking Deposit & Play you accept our <b> <a href="/mobile/privacy-policy/">Privacy Notice</a></b> and <b> <a href="/mobile/terms-and-conditions/">Terms & Conditions.</a></b>'
        ]
    ];
}
