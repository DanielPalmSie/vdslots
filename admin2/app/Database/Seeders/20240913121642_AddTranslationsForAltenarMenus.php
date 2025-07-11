<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddTranslationsForAltenarMenus extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'menu.secondary.altenar' => 'Sports'
        ],
        'sv' => [
            'menu.secondary.altenar' => 'Sport',
        ]
    ];
}