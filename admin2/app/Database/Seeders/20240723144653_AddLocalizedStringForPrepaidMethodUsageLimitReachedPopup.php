<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForPrepaidMethodUsageLimitReachedPopup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'prepaid.method.usage.limit.reached.title' => 'Prepaid Method Usage Limit Reached',
            'prepaid.method.usage.limit.reached.description' => 'You have reached the maximum allowed of {{max_allowed_cards}} prepaid methods within a rolling {{last_days}}-day period.',
            'prepaid.method.usage.limit.reached.btn' => 'Ok'
        ]
    ];
}