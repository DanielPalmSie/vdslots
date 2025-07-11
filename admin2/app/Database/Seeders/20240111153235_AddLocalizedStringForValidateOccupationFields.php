<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForValidateOccupationFields extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'occupational.form.validation.checkboxRequired' => "Checkbox check is required",
            'occupational.form.validation.occupationRequired' => 'Occupation is required',
            'occupational.form.validation.alphaSingleSpace' => 'Only alphabetical characters and single space between words are allowed',
            'occupational.form.validation.monthlyBudgetRequired' => 'Monthly budget amount is required',
        ]
    ];
}
