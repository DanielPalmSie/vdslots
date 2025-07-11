<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddBuildingLocalizedString extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [ 'en' => [
        'account.building' => 'Building',
        'register.building' => 'Building',
        'register.err.building' => 'not numerical',
    ]];
}