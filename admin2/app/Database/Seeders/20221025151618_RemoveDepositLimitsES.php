<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class RemoveDepositLimitsES extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'remove.deposit.limit.title' => 'Decrease Deposit Limit',
            'remove.deposit.limit.description' => 'To request removal of your deposit limit please complete this short questionnaire.',
            'remove.deposit.limit.locked' => 'Remove in Limits is Locked',
            'increase.deposit.limit.locked' => 'Increase in Limits is Locked',
            'confirmation.deposit.message' => 'Thank you for completing the test. Once your results are evaluated and request is successful, limits will increase after 3 days',
        ]
    ];
}