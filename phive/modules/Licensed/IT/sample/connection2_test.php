<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit');

$data = [
    'transaction_id'     => '12345',
    'account_code'       => '1433',
    'email'              => 'testeracme.com',
];

print_r(lic('updateEmailAccount', [$data], $user));


