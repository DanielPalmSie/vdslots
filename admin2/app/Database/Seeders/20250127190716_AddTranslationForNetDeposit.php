<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddTranslationForNetDeposit extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'customer_net_deposit.limit.headline' => 'Set Net Deposit Limit',
            'customer_net_deposit.month.ly.descr' => '( 30 days )',
            'customer_net_deposit.limit.info.html' => '<p style="text-align: justify;">Set a net deposit limit for a chosen period. Once reached, you\'ll be notified and unable to deposit.</p>
                                                <p style="text-align: justify;">You can adjust or remove the limit, effective after {{cooloff_period}} days (immediately if lowering).</p> 
                                                <p style="text-align: justify;">Check your remaining net deposit limit balance here anytime.</p>',
            'customer_net_deposit.box.top.headline' => 'Message',
            'customer_net_deposit.box.description.html' => "You've reached your Net Deposit Limit. You can deposit <b>{{depositAmount}}{{currency}}</b> until <b>{{tillDate}}</b>. Do you wish to proceed?",
        ],
    ];
}