<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => 'farnezi01',
    'limit_management' => 1,
    'limit' => [
        'limit_type' => 1,
        'amount' => 100,

    ],
    'transaction_id' => time()
];


print_r(lic('updateAccountLimit', [$data], $user));