<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForNationalityPOBPopup extends SeederTranslation
{
    protected array $data = [
            'en' => [
                    'select.nationalityandpob.description' => 'We require you to insert the below information before you can proceed to game play.',
                    'nationality.default.select.option' => 'Nationality',
                    'nationality.error.description' => 'Nationality must be selected.',
                    'birthcountry.default.select.option' => 'Country of Birth',
                    'birthcountry.error.description' => 'Country of Birth must be selected.',
                    'nationalityandpob.saved.success' => 'Nationality and Country of Birth were saved successfully.',
            ],
    ];
}