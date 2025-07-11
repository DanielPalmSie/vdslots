<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForMobileFilterViewAllCategories extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'view-all.cgames' => 'View All',
        ],
    ];
}
