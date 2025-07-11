<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class TranslationMyVouchersPhraseForMobile extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'my.vouchers' => 'My Vouchers',
        ],
        'sv' => [
            'my.vouchers' => 'Mina värdebevis',
        ],
    ];

    protected array $stringConnectionsData = [
        'tag' => 'mobile_app_localization_tag',
        'bonus_code' => 0,
    ];
}
