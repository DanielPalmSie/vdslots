<?php
namespace IT\Services;

use DBUser;
use DateTime;
use IT\Pacg\PacgTrait;
use IT\Pacg\Codes\ReturnCode as PacgReturnCode;

/**
 * Class AccountTransactionService
 * @package IT\Services
 */
class AccountTransactionService
{
    use PacgTrait;

    const TRANSACTION_REASONS = [
        'deposit' => 1,
        'withdraw' => 3
    ];

    /**
     * Payment methods:
     *   2  Credit Card
     *   3  Debit Card
     *   4  Bank/Post Office Transfer
     *   5  Postal Order
     *   6  Current Account Check
     *   7  Cashier's Check
     *   8  Money Order
     *   9  Scratch Top Up
     *  11  ELMI
     *  12  Gambling Account
     *  13  Conversion from Bonus
     *  14  E-Wallet
     *  15  Point of Sale
     *  16 Payment Institute
     */
    const PAYMENT_METHODS = [
        'ccard' => 2,
        'debit_card' => 3,
        'bank' => 4,
        'postal_order' => 5,
        'current_account' => 6,
        'cashier' => 7,
        'money_order' => 8,
        'scratch' => 9,
        'elmi' => 11,
        'gambling' => 12,
        'bonus' => 13,
        'ewallet' => 14,
        'pcard' => 15,
        'payment_institute' => 16
    ];

    /**
     * @var DBUser
     */
    private DBUser $user;

    /**
     * Italian settings
     * @var array
     */
    private array $settings;

    /**
     * @var DateTime
     */
    private DateTime $now;

    /**
     * EmailAccountService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->settings =  phive('Licensed')->getSetting('IT');
        $this->now = new DateTime();
    }

    /**
     * @param string $payment_method_name
     * @return int
     */
    private function getPaymentType(string $payment_method_name): string
    {
        if (in_array($payment_method_name, ['account', 'bonus'])) {
            return self::PAYMENT_METHODS[$payment_method_name];
        }

        $types = phive('CasinoCashier')->getFullPspConfig();
        foreach ($types as $payment_method => $payment_items) {
            if ($payment_method == $payment_method_name) {
                return self::PAYMENT_METHODS[$payment_items['type'] ?? 'ccard'];
            }
        }

        return 0;
    }

    /**
     * @param $user
     * @return int
     */
    private function getBonus($user = null): int
    {
        $stake = phive('Casino')->balances($user);
        return $stake['bonus_balance'] ?? 0;
    }

    /**
     * @param string $action
     * @param string $supplier
     * @param int $value
     * @param DBUser $user
     * @return array
     */
    public function getPayload(string $action, string $supplier, int $value, DBUser $user): array
    {
        $balance_amount= $user->getBalance() + $this->getBonus($user);

        return [
            'account_code' => $user->getData('id'),
            'account_sales_network_id' => $this->settings['network_id'],
            'account_network_id' => $this->settings['id_cn'],
            'transaction_reason' => self::TRANSACTION_REASONS[$action],
            'transaction_amount' => $value,
            'balance_amount' => (int) $balance_amount,
            'total_bonus_balance_on_account' => $this->getBonus($user),
            'transaction_id' => time(),
            'payment_method' => $this->getPaymentType($supplier),
            'transaction_datetime' => [
                'date' => [
                    'day' => $this->now->format('d'),
                    'month' => $this->now->format('m'),
                    'year' => $this->now->format('Y')
                ],
                'time' => [
                    'hours' => $this->now->format('H'),
                    'minutes' => $this->now->format('i'),
                    'seconds' => $this->now->format('s')
                ]
            ]
        ];
    }

    /**
     * Dispatch report transaction job to queue
     *
     * @param string $action
     * @param string $supplier
     * @param int    $value
     * @param int    $user_id
     * @param bool   $async
     * @return array
     * @throws \Exception
     */
    public function dispatchReportTransactionJob(string $action, string $supplier, int $value, int $user_id, bool $async = true)
    {
        if ($async) {
            return phive('Site/Publisher')->singleNoLB(
                'pacg',
                'Licensed',
                'doLicense',
                ['IT', 'reportTransaction', [$action, $supplier, $value,  $user_id, 0]],
                'rabbit3' // QS3
            );
        } else {
            return $this->reportTransaction($action, $supplier, $value,  $user_id);
        }
    }

    /**
     * Report transaction to ADM
     *
     * @param string $action
     * @param string $supplier
     * @param int    $value
     * @param int    $user_id
     * @param int    $attempt
     * @return array
     * @throws \Exception
     */
    public function reportTransaction(string $action, string $supplier, int $value, int $user_id, int $attempt = 0)
    {
        $user = cu($user_id);

        $payload = $this->getPayload($action, $supplier, $value, $user);
        $response = $this->accountTransactions($payload);

        if ($response['code'] != PacgReturnCode::SUCCESS_CODE) {
            $logMessage = "An error has occurred during the {$action} ({$supplier}) of {$value}. Error code: '{$response['code']}', message: '{$response['message']}', attempt: {$attempt}";
            dumpTbl('pacg-account-transaction-error', ['payload' => json_encode($payload), 'response' => json_encode($response), 'message' => $logMessage]);
            // store the error inside actions
            phive('UserHandler')->logAction($user, $logMessage, 'pacg-account-transaction-error', false, $user);

            if(in_array($response['code'], [1025, 1026, 1029]) and $attempt < 2) {
                phive('Site/Publisher')->single('pacg', 'Licensed', 'doLicense', ['IT', 'reportTransaction', [$action, $supplier, $value, $user_id, ($attempt + 1)]]);
            }
        }

        return $response;
    }
}
