<?php
use App\Extensions\Database\Seeder\SeederTranslation;

/**
 * ./console seeder:up 20250122142251
 *
 * ./console seeder:down 20250122142251
 */
class AddLocalizedStringForBetReceiptCashout extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'sports-history.receipt.cashout' => 'Cashout',
            'sports-history.receipt.cashout-stake' => 'Cashout Stake',
            'sports-history.receipt.partial-cashout' => 'Partial Cashout',
            'sports-history.receipt.partial-cashout-stake' => 'Partial Cashout Stake',
        ],
    ];
}
