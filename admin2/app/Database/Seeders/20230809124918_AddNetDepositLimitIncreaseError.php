<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddNetDepositLimitIncreaseError extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'api.net.deposit.limit.not.reached.error' => 'Net deposit limit has not been reached',
            ]
    ];
}
