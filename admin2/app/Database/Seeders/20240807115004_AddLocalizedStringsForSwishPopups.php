<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForSwishPopups extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'paynplay.deposit.confirmation' => 'Your attempted deposit of {{currency}} {{attempted_amount}} exceeds the allowed maximum limit of {{currency}} {{maximum_amount}}.
            To comply with the deposit limit, please reduce your deposit amount to {{currency}} {{maximum_amount}} or less.',
            'paynplay.open.app' => 'To proceed with the payment, open the Swish app on your mobile device.',
            'paynplay.scan.qr' => 'Scan this QR code with the Swish app on your device (supported on smartphone and tablet).',
            'paynplay.confirm' => 'Confirm deposit amount',
        ],
    ];
}
