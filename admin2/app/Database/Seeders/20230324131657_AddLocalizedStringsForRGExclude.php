<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForRGExclude extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'api.user.error.rg.exclude.duration.invalid' => 'The selected duration is invalid.',
        ]
    ];
}