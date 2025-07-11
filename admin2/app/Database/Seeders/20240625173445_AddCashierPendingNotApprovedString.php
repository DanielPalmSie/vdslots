<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddCashierPendingNotApprovedString extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'cashier.pending_not_approved' => 'Pending withdrawal disapproved',
        ]
    ];
}
