<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddSystemCommentsForSkippedRgPopup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'rg.popup.skipped.user.comment' => '{{trigger}} flag triggered with {{tag}} GRS, but a popup has already been shown within the {{popup_interval}} mins so no popup was displayed.',
        ]
    ];
}