<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForInvalidEmailFormate extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'invalid.format.err' => 'invalid format',
        ]
    ];
}
