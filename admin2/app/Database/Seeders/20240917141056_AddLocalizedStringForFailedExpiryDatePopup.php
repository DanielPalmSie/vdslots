<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForFailedExpiryDatePopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'idscan.failed.expiry.date.message' => "We need some additional details to approve your ID card, Please contact support. <b>{{mail}}</b>",
            'idscan.failed.expiry.date.header' => "ID card Info",
        ]
    ];
}
