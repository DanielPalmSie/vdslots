<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddTranslationsForSuperstipMenus extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'menu.secondary.poolx' => 'Superstip'
        ],
        'sv' => [
            'menu.secondary.poolx' => 'Superstip',
        ]
    ];
}
