<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForContentNotFound extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'content.not.found' => 'Content not found',
        ]
    ];
}
