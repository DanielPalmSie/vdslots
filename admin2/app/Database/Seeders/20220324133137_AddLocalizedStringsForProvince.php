<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForProvince extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'select.province.description' => 'To complete your login, please select your province.',
            'province.header' => 'Select your Province',
            'province.default.select.option' => 'Choose Province',
            'province.error.description' => 'Province must be selected.',
            'province.saved.success' => 'Province information saved successfully.'
        ]
    ];
}