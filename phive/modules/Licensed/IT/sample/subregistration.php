<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => 'farnezi01',
    'balance_amount' => '5000',
    'balance_bonus_amount' => '0',
    'transaction_id' => time()
];


print_r(lic('subregistration', [$data], $user));