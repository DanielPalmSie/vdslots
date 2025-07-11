<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class IncreaseDepositLimitsES extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'increase.deposit.limit.title' => 'Increase Deposit Limit',
            'increase.deposit.limit.description' => 'To request an increase to your deposit limit please complete this short questionnaire.',
            'statement' => 'Statement',
            'questionnaire.increase.deposit.limit.step1' => 'Do you often think about past gaming or gambling experiences, planning the next time you will play and/or 
                                                             do you find yourself working out how to obtain money to spend on gambling?',
            'questionnaire.increase.deposit.limit.step2' => 'Have you ever spent more money on gaming and gambling than you had initially planned?',
            'questionnaire.increase.deposit.limit.step3' => 'Do you ever try to control, interrupt or stop the game?',
            'questionnaire.increase.deposit.limit.step4' => 'Does trying to interrupt or stop the game make you feel restless or irritable?',
            'questionnaire.increase.deposit.limit.step5' => 'Do you play to evade problems?',
            'questionnaire.increase.deposit.limit.step6' => 'When you play for money, do you ever play again so you can recover the money you have lost?',
            'questionnaire.increase.deposit.limit.step7' => 'Do you think you have a gambling problem?',
            'questionnaire.increase.deposit.limit.step8' => 'Do you use money obtained from your family, loans, falsification, fraud or theft to finance your gambling activities?',
            'questionnaire.increase.deposit.limit.step9' => 'Has gambling ever made you miss work or class?',
            'questionnaire.increase.deposit.limit.step10' => 'Have you ever asked anyone to help you out with the financial problems gambling has caused you?',
            'increase.deposit.limit.test.complete' => 'Test Complete!'
        ]
    ];
}