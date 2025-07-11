<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('ancora@test.it');
$data = [
    'account_code' => uid($user),
    'bonus_receipt_id' => time(),
    'transaction_reason' => '6', //Bonus Reversal
    'bonus_cancelation_type'=> '1', // Ordinary reversal
    'bonus_cancelation_amount' => '5000',  // This is the total bonus amount on the game account including what is being sent
    'bonus_details' => [
        [
            'gaming_family' => '6',
            'gaming_type' => '2',
            'bonus_amount' => '5000',
        ]

    ],
    'balance_amount' => 10000,
    'bonus_balance_amount' => 5000,
    'bonus_balance_details' => [   // All the bonuses present on the account, including this transaction, grouped by gaming_family/gaming_type combination
        [
            'gaming_family' => '6',
            'gaming_type' => '2',
            'bonus_amount' => '5000',  // Bonus being sent including bonus already present, for this gaming_family/gaming_type combination
        ]
    ],
];

var_dump(lic('bonusReversalAccountTransaction', [$data], $user));



