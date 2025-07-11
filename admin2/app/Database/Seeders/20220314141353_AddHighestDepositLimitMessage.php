<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddHighestDepositLimitMessage extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'rg.highest.allowed.deposit.limit.error' => 'The maximum deposit limit you are allowed to set is {{limit}} euros. Please contact our Customer Service via live char or email (support@videoslots.com) if you have any further questions.',
            'rg.up.to.x.limit' => 'Up to {{limit}} EUR allowed'
        ]
    ];
}