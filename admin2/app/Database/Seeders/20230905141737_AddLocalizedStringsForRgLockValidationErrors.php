<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForRgLockValidationErrors extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'profile.lock-section.empty-input-error' => 'Please fill in the form',
            'profile.lock-section.empty-selection-error' => 'Please select one of the options',
        ],
    ];
}
