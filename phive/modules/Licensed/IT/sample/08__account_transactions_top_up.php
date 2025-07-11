<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

//"idReteConto"   => $this->account_sales_network_id,
//"idCnConto"     => $this->account_network_id,

/**
 * Transaction reasons:
 *   1 Top Up
 *   2 Top Up Reversal
 *   3 Withdrawal
 *   4 Withdrawal Reversal
 *   7 Additional Service Costs
 *   8 Additional Service Cost Reversal
 */

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
 *  16 Payment Institute art. 1, paragraph 2, lett. h- septies.l. nos. 3 and 6 of Italian Legislative Decree No. 385/1993
 */

$data = [
    'account_code' => $userid_01,
    'account_sales_network_id' => '14',
    'account_network_id' => '15427',
    'transaction_reason' => '1', //Top Up
    'transaction_amount' => '5000',
    'balance_amount' => '11000',   // Balance was 9000, after deposit 5000, it will be 13000
    'total_bonus_balance_on_account' => '3000',
    'transaction_id' => time(),
    'payment_method' => '2',
    'transaction_datetime' => [
        'date' => [
            'day' => date('d'),
            'month' => date('m'),
            'year' => date('Y')
        ],
        'time' => [
            'hours' => date('H'),
            'minutes' => date('i'),
            'seconds' => date('s')
        ]
    ],
    'bonus_details' => [  // All the bonuses present on the account
        [
            'gaming_family' => '6',
            'gaming_type' => '2',
            'bonus_amount' => '3000',  // Bonus being reversed
        ],
    ],

];

print_r(lic('accountTransactions', [$data], $user));



