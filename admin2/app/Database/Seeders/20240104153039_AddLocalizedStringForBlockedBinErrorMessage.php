<?php

use \App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForBlockedBinErrorMessage extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'card.bin.not.allowed' => "You are currently not allowed to deposit with this card. Please use one of our alternative methods or contact customer services.",
        ]
    ];
}
