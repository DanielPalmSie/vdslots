<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForPayNPLayErrors extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'paynplay.error-popup.proceed' => 'OK',
            'paynplay.error.blocked.title' => 'Account Blocked / Restricted',
            'paynplay.error.blocked.description' => '<p>For some reason your account has been deactivated.</p><p>Please <a href="/customer-service/">contact support</a> for more information.</p>',
            'paynplay.error.self-excluded.title' => 'Account Disabled',
            'paynplay.error.self-excluded.description' => '<p>For some reason your account has been disabled.</p><p>Please <a href="/customer-service/">contact support</a> for more information.</p>',
            'paynplay.error.deposit-reached.title' => 'Transaction Failed',
            'paynplay.error.deposit-reached.description' => '<p>You have deposited more money than you are allowed to during a given period.</p><p>Please check back in a few days.</p>',
            'paynplay.error.login-limit-reached.title' => 'Login Limit Reached',
            'paynplay.error.login-limit-reached.description' => 'Your account is temporarily locked because your login limit has been reached. You are welcome back when the limit has been reset.',
            'paynplay.error.monthly-net-deposit-limit-reached.title' => 'Monthly Net Deposit Limit',
            'paynplay.error.monthly-net-deposit-limit-reached.description' => '<p>Because your safety is important to us, you cannot deposit at the moment because you have reached your Casino Net Deposit Limit. This limit will reset at the end of the month at 00:00 GMT.</p><p> If you wish to increase this limit, click the ‘Request limit increase’ button below and a support agent will be in contact with you.</p>',
            'paynplay.error.monthly-net-deposit-limit-reached.increase' => 'Request Limit Increase',
        ]
    ];
}
