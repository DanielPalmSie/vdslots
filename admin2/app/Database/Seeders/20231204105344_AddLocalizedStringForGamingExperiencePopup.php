<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForGamingExperiencePopup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'rg.info.limits.show.headline' => 'Gaming Experience!',
            'rg.info.box.top.headline' => 'Welcome Back',
            'rg.info.box.top.html' => '<p>We hope you will have an exciting experience.Remember to keep track of your gambling.<br />
                                        Take a look at our <a style="color: #000000;font-weight: bold;" href="/sv/?global_redirect=rg">Responsible Gaming</a> section and test the different limits.</p>',
            'rg.info.box.last_login_date' => '<b>Last Login: {{date}}</b>',
            'rg.info.popup.winloss' => 'Game results at Kungaslottet',
            'rg.info.popup.winloss.period' => 'Last 12 months (from 2022)',
            'rg.info.popup.winloss.period.se' => 'Last 12 months (from 2022)',
            'show.sum' => '<span class="total-desc">Show total.</span>',
            'rg.info.your.limits' => 'Your Game Limits',
            'rg.info.deposit.limits' => 'Deposit Limit',
            'rg.info.login.limits' => 'Login Limit',
            'rg.info.loss.limits' => 'Loss Limit',
            'rg.info.wager.limits' => 'Wager Limit',
            'rg.info.loss-sportsbook.limits' => 'Loss Limit Sports',
            'rg.info.wager-sportsbook.limits' => 'Wager Limit Sports',
            'rg.info.day.limits' => 'Daily Limit',
            'rg.info.week.limits' => 'Weekly Limit',
            'rg.info.month.limits' => 'Monthly Limit',
            'rg.info.box.action.info' => 'If you wish to change your game limits, please visit the limits page.',
            'rg.info.edit.limits' => 'Change your Limits'
        ]
    ];
}