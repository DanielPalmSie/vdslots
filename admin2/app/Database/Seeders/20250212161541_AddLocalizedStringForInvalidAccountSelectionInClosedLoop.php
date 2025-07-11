<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForInvalidAccountSelectionInClosedLoop extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'closed.loop.invalid.account.selection' =>
                'This option {{account}} is disabled due to pending closed loop withdrawals {{validAccounts}}',
            'closed.loop.select.alternative.account.btn' => 'Withdraw'
        ],
    ];
}
