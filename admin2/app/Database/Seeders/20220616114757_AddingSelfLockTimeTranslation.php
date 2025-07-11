<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddingSelfLockTimeTranslation extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'exclude.1460.hours' => '2 Months',
            'exclude.2190.hours' => '3 Months'
        ]
    ];
}