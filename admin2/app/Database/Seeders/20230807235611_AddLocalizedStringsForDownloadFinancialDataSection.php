<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForDownloadFinancialDataSection extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'game-history.download-financial-data' => 'Download my financial data',
            'game-history.download-financial-data-for-interval' => 'Download financial data for the last 12 months',
            'game-history.overview' => 'Overview',
            'game-history.prizes' => 'Prizes',
            'game-history.timestamp' => 'Timestamp',
            'game-history.date' => 'Date',
            'game-history.amount' => 'Amount',
            'game-history.description' => 'Description',
            'game-history.status' => 'Status',
            'game-history.requested-date' => 'Requested Date',
            'game-history.closing-balance' => 'Closing Balance',
            'game-history.opening-balance' => 'Opening Balance',
            'game-history.sum-of-withdrawals' => 'Sum of Withdrawals',
            'game-history.sum-of-deposits' => 'Sum of Deposits',
            'game-history.sum-of-charges' => 'Sum of Charges (Wagers)',
            'game-history.sum-of-charges-other' => 'Sum of Charges (Other Fees)',
            'game-history.sum-of-charges-total' => 'Total Sum of Charges',
        ],
    ];
}
