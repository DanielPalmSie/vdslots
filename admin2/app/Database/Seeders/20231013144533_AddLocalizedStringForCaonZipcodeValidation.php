<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForCaonZipcodeValidation extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'zipcode.unvalid.CA-ON' => "Invalid postal code format. Please enter a valid Ontario postal code in the format 'A1A 1A1' (e.g., K1A 0B1).",
            'register.err.zipcode.unvalid.CA-ON' => "Invalid postal code format. Please enter a valid Ontario postal code in the format 'A1A 1A1' (e.g., K1A 0B1)."
        ]
    ];
}
