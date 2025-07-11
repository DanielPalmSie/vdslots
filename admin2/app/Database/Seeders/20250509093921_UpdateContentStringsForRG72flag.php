<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class UpdateContentStringsForRG72flag extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'RG72.user.comment' => '
                       RG72 Flag was triggered. User has risk of {{tag}}. User has a net deposit of {{net_deposit_amount}} within {{time}} hours. An interaction via popup in gameplay was made to inform the customer that we noticed {{deposit_amount}} deposits in short period of time. We recommend the player to review their limits and to keep their gambling experience at fun and secure levels.',

        ]
    ];
}
