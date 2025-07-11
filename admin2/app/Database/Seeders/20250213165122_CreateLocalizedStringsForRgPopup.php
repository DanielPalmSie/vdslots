<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class CreateLocalizedStringsForRgPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'RG9.rg.info.description.html' => '
                       You have recently changed your deposit or wager limits several times.<br/>
                       It\'s important to play within comfortable levels.<br/>
                       We recommend you to review your limits within comfortable levels.',
            'RG15.rg.info.description.html' => '
                       You have just placed a bet equal to 100% of your last deposit.<br/>
                       Please ensure you\'re playing within comfortable limits. Review our responsible gambling tools and put them on a safe and fun level.',
            'RG16.rg.info.description.html' => '
                       We have noticed that you\'ve resumed activity on an account which was previously self-locked.<br/>
                       Are you ready to come back?<br/>
                       If you feel the need for additional support, please review our responsible gambling tools or consider taking a break!',
            'RG18.rg.info.description.html' => '
                       We have noticed multiple failed deposit attempts today.<br/>
                       Maybe it\'s time to take a break?<br/>
                       It\'s important to ensure you\'re playing within comfortable levels and have the funds necessary to gamble.<br/>
                       Remember, gambling should be fun and safe!',
            'RG58.rg.info.description.html' => '
                       We have noticed that your gaming sessions have exceeded one hour on consecutive days.<br/>
                       It\'s important to take regular breaks and ensure you\'re playing within comfortable levels. Please consider taking a moment to pause you session.',
            'RG66.rg.info.description.html' => '
                       Your net deposits amount reached {{deposit_amount}} within the last {{days}} days.<br/>
                       Please consider reviewing your spending and ensure you are within comfortable levels.<br/>
                       Our responsible gaming tools are available to assist you to maintain your activity on a safe and fun level.',
            'RG9.user.comment' => '
                       RG9 Flag was triggered. User changed his limit {{attempts_of_limit_changing}} times. An interaction via popup in gameplay was made to inform the customer that we noticed he changed his limits several times. We advised the player to review their limits within comfortable levels.',
            'RG15.user.comment' => '
                       RG15 Flag triggered. User placed single bet of 100% his last deposit. An interaction via popup in gameplay was made to inform the customer that we noticed his activity. We advised the player to review their limits and set them on a safe and fun level.',
            'RG16.user.comment' => '
                       RG16 Flag triggered. User returns from self-lock. An interaction via popup in gameplay was made to inform the customer that we have noticed he\'s back. We advised the player to review their limits or consider taking a break.',
            'RG18.user.comment' => '
                       RG18 Flag triggered. User has {{amount_of_failed_deposit}} failed deposit transactions on the same day. An interaction via popup in gameplay was made to inform the customer that we have noticed the activity and have recommended him to take a break and make sure has the funds to gamble, as gambling should be fun and safe!',
            'RG58.user.comment' => '
                       RG58 Flag triggered. User has game session duration on consecutive day for over {{hours}} hour. An interaction via popup in gameplay was made to inform the customer that we have noticed the activity and have recommended to take a break and ensure he is playing withing comfortable levels',
            'RG66.user.comment' => '
                       RG66 Flag triggered. User has reached Net Deposit of {{deposit_amount}} within the last {{days}} days. An interaction via popup in gameplay was made to inform the customer that we have noticed the activity and have recommended reviewing spending levels and using our responsible gaming tools.',

        ]
    ];
}
