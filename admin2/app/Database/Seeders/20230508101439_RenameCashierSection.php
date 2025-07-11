<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class RenameCashierSection extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'ebank' => 'Bank Transfers'
        ],
        'on' => [
            'ebank' => 'Bank Transfers'
        ],
    ];
}