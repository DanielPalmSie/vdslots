<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class UpdateContentStringsForRgPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'RG27.rg.info.description.html' => '
                       We\'ve noticed a recent deposit on your account.<br/>
                       It\'s important to ensure you\'re playing within safe and comfortable limits.<br/>
                       Please take a moment to review your activity and consider adjusting your limits for a balanced gaming experience.',
            'RG37.rg.info.description.html' => '
                       We\'ve noticed some changes in your recent gaming activity.<br/>
                       For your safety and well-being, we strongly recommend that you review your recent activity and take a moment to assess your limits. <br/>
                       Playing responsibly is essential to keeping your experience fun and secure.',
            'RG70.rg.info.description.html' => '
                       We\'ve identified some important information regarding your financial well-being.<br/>
                       Before you start playing, we recommend reviewing your limits to ensure safe play.',
            'RG72.rg.info.description.html' => '
                       You have deposited {{deposit_amount}} in a short period of time.<br/>
                       It\'s important to ensure that you\'re gaming activity remains within comfortable limits. <br/>
                       Please consider reviewing your deposit history and adjusting your limits to keep your experience safe and enjoyable.',
            'RG27.user.comment' => '
                       RG27 Flag was triggered. Deposit from a user in {{tag}} risk. An interaction via popup in gameplay was made to inform the customer that we noticed that a recent deposit was made in the account. We recommend the player to review their limits and to keep their gambling at balanced level.',
            'RG37.user.comment' => '
                       RG37 Flag was triggered. User is {{tag}} risk. An interaction via popup in gameplay was made to inform the customer that we noticed changes in recent gaming activity. We recommend the player to review their limits and to keep their gambling experience at fun and secure levels.',
            'RG70.user.comment' => '
                       RG70 Flag was triggered. User\'s FVC check returned vulnerable. An interaction via popup in gameplay was made to inform the customer that we have some information about his financial well-being. We recommend the player to review their limits and to ensure safe play.',
            'RG72.user.comment' => '
                       RG72 Flag was triggered. User has net deposit {{deposit_amount}} within {{time}} hours. An interaction via popup in gameplay was made to inform the customer that we noticed {{deposit_amount}} deposits in short period of time. We recommend the player to review their limits and to keep their gambling experience at fun and secure levels.',

        ]
    ];
}
