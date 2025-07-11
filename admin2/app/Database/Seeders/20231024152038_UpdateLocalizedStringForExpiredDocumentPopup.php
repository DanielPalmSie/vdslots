<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class UpdateLocalizedStringForExpiredDocumentPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'restrict.msg.expired.documents.btn' => 'Ok'
        ]
    ];
}
