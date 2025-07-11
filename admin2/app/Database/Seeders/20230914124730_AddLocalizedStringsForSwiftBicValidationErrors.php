<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForSwiftBicValidationErrors extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'cashier.error.required_valid_swift_bic'   => 'Please specify a valid SWIFT/BIC',
        ]
    ];
}
