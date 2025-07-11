<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class UpdatingDocumentSectionString extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'upload.instructions.headline' => 'Your uploaded files must be in any of the following formats:'
        ]
    ];
}
