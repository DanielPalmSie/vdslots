<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForProfileProvinceFields extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'account.province' => 'Province:',
            'register.province' => 'Province*',
            'register.err.province'  => 'Invalid Province'
        ]
    ];
}
