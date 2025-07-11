<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class CreateRgPopupContentAlias extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'RG5.rg.info.description.html' => '
                       As you have taken a break for a while we recommend you to review your limits within our responsible gambling tools and put them on a safe and fun level before you start to play again.<br/>
                       Its important to play within comfortable levels.',
            'RG24.rg.info.description.html' => '
                       We have noticed multiple declined deposit attempts.<br/>
                       Having trouble depositing?<br/>
                       It might be a good time to review our responsible gambling tools or consider taking a break!',
            'RG25.rg.info.description.html' => '
                       We have noticed multiple failed deposit attempts in a short time.<br/>
                       Having trouble depositing?<br/>
                       We recommend you to review your limits within our responsible gambling tools and put them on a safe and fun level.',
            'RG62.rg.info.description.html' => '
                       We\'ve noticed you\'ve been logging in frequently in the last week.<br/>
                       Taking regular breaks is important to keep your play fun and balanced.<br/>
                       We recommend you to review our responsible gambling tools.<br/>
                       Remember, gambling should be fun and safe!',
            'RG63.rg.info.description.html' => '
                       We\'ve noticed you\'ve been logging in frequently this month.<br/>
                       Taking regular breaks is important to keep your play fun and balanced.<br/>
                       We recommend you to review our responsible gambling tools.<br/>
                       Remember, gambling should be fun and safe!',
            'RG64.rg.info.description.html' => '
                       We\'ve noticed a high number of deposits today.<br/>
                       Please take a moment to review your spending and ensure you\'re playing within safe limits.<br/>
                       We recommend using our responsible gambling tools to help manage your play.',
            'RG67.rg.info.description.html' => '
                       We\'ve noticed significant losses on your account.<br/>
                       Gambling should always be fun and within your limits.<br/>
                       Please consider reviewing your limits within our responsible gambling tools and put them on a safe and fun level.',
            'RG5.user.comment' => '
                       RG5 Flag was triggered. User has risk {{tag}}. An interaction via popup in gameplay was made to inform customer that we notice that the user have been on a break from gambling. Before the user starts to play, we recommend them to review their limits and to keep their gambling at a safe and fun level.',
            'RG24.user.comment' => '
                       RG24 Flag has triggered. User has {{amount_of_declined_deposits}} declined deposits. An interaction via popup in gameplay was made to inform the customer that we noticed multiple declined deposit attempts and the use of different cards. We recommend the player to review their spending so their gambling will be kept at a safe and fun level.',
            'RG25.user.comment' => '
                       RG25 Flag was triggered. User has {{amount_of_failed_deposits}} failed deposits. An interaction via popup in gameplay was made to inform the customer that we noticed multiple failed deposit attempts made in quick succession without placing any bets. We recommend the player to review their spending so gambling remains safe and manageable.',
            'RG62.user.comment' => '
                       RG62 Flag was triggered. User has risk {{tag}}. An interaction via popup in gameplay was made to inform the customer that we noticed high login frequency during the week. The player was encouraged to take breaks and ensure their gambling remains safe and balanced.',
            'RG63.user.comment' => '
                       RG63 Flag was triggered. User has risk {{tag}}. An interaction via popup in gameplay was made to inform the customer that we noticed high login frequency over the past month. The player was encouraged to take breaks and ensure their gambling remains safe and balanced.',
            'RG64.user.comment' => '
                       RG64 Flag was triggered. User has risk {{tag}}. An interaction via popup in gameplay was made to inform the customer that we noticed high number of deposits within {{hours}} hours. The player was encouraged to review their spending habits and ensure gambling remains safe and manageable.',
            'RG67.user.comment' => '
                       RG67 Flag was triggered. User has risk {{tag}}. An interaction via popup in gameplay was made to inform the customer that we have noticed significant net losses. We recommend the player to review their limits and to ensure safe play.',

        ]
    ];

}
