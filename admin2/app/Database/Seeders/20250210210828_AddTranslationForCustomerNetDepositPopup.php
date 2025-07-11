<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddTranslationForCustomerNetDepositPopup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'customer.net.deposit.limit.info.month.header' => 'Monthly Net Deposit Limit',
            'customer.net.deposit.limit.info.month.body.html' => '<p>You have reached your monthly limit. We have a default monthly Net Deposit Limit set for all customers.&nbsp;</p>
<p><strong>The limit resets at the end of each calendar month at 00:00 GMT.</strong></p>
<p>If you wish to increase your limit, click the "Request limit increase" and an agent will be in contact with you.</p>
<p>The limit is there to protect our customers not to bet more than they had in mind.</p>',
            'customer.net.deposit.limit.info.request.accept.button' => 'Ok',
            'customer.net.deposit.limit.info.request.increase.button' => 'Request limit increase',
            'customer.net.deposit.limit.info.request.increase.success.message' => 'Your request to increase the limit was submitted successfully, an agent will contact you soon.',
            'customer.net.deposit.limit.info.title' => 'Message',
        ]
    ];
}