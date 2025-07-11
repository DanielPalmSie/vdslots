<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class EsThirtyDayErrMsg extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'can.use.award.license.refusal' => 'Due to regulatory requirements you have to wait {{1}} days before you can activate this award.'
        ]
    ];
}
