<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

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
    'transaction_receipt_id' => 'dc07e406130200000000000e4',  // reversing the first deposit
    /*'account_sales_network_id' => '14',
    'account_network_id' => '15427',*/
    'payment_method' => '9',
    'transaction_description' => '2', //Top Up Reversal
    'reversal_type' => '1',
    'transaction_amount' => '10000',  // balance was 5000, revering a deposit from 5000, so balance will be 5000
    'balance_amount' => '5000',
    'balance_bonus_amount' => '0',
    'datetime' => [
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
    'transaction_id' => time(),
];


var_dump(lic('reversalAccountTransactions', [$data], $user));