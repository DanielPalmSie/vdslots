<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


/**
 * Gaming family: 6
 * Gaming type:   2
 *  (6-2 => Fixed Odds Chance Games)
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
 *  16  Payment Institute art. 1, paragraph 2, lett. h- septies.l. nos. 3 and 6 of Italian Legislative Decree No. 385/1993
 */

/**
 * Transaction reasons:
 *   1 Top Up
 *   2 Top Up Reversal
 *   3 Withdrawal
 *   4 Withdrawal Reversal
 *   5 Bonus
 *   6 Bonus Reversal
 *   7 Additional Service Costs
 *   8 Additional Service Cost Reversal
 */

$user = cu('devtestit002');    // dc07e4060f160000000000149
$data = [
    'account_code' => 'testpacg05',
    'payment_method' => '1',
    'total_bonus_balance_on_account' => '3000',  // This is the total bonus amount on the game account including what is being sent
    'bonus_balance_amount' => '3000',
    'balance_amount' => '9000',    // Balance was 12000, after reversing 3000 bonus, it will be 9000
    'transaction_reason' => '6', //Bonus reversal
    'transaction_amount' => '3000',
    'transaction_id' => time(),
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
    'bonus_details' => [  // All the bonuses being send by this transaction, grouped by gaming_family/gaming_type combination
        [
            'gaming_family' => '6',
            'gaming_type' => '2',
            'bonus_amount' => '3000',  // Bonus being reversed
        ],
    ],
    'bonus_balance_details' => [   // All the bonuses present on the account, including this transaction
        [
            'gaming_family' => '6',
            'gaming_type' => '2',
            'bonus_amount' => '3000',  // Bonus present in the account after this transaction
        ],
        [
            'gaming_family' => '1',
            'gaming_type' => '2',
            'bonus_amount' => '3000',
        ],
    ],

];


print_r(lic('bonusAccountTransactions', [$data], $user));



