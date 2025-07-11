<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_02,
    'limit_management' => 1,
    'limit' => [
        'limit_type' => 2,
        'amount' => 10000,

    ],
    'transaction_id' => time()
];


print_r(lic('updateAccountLimit', [$data], $user));