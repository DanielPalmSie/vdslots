<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForNidValidation extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'nid.validation.invalid.format' => 'The nid must be an integer.',
        ]
    ];
}
