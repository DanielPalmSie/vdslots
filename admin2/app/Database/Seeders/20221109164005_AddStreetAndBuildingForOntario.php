<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddStreetAndBuildingForOntario extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'register.street.nostar' => 'Street',
            'register.building.nostar' => 'Building',
        ]
    ];

}