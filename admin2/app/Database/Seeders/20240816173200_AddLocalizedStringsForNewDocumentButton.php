<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForNewDocumentButton extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'document.choose.file' => "Choose file",
            'document.no.file.chosen' => "No file chosen",
        ]
    ];
}
