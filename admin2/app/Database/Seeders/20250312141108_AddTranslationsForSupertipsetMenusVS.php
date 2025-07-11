<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddTranslationsForSupertipsetMenusVS extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'menu.secondary.poolx' => 'Supertipset'
        ],
        'sv' => [
            'menu.secondary.poolx' => 'Supertipset',
        ]
    ];
}
