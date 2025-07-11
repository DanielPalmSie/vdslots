<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForPayNPlayHeader extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'paynplay.login' => 'Start Playing',
        ]
    ];
}
