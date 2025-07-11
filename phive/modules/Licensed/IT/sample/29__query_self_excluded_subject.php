<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';

$user = cu('devtestit002');

$data = [
    'tax_code' => 'AAABBB75L17H501Q',
//    'transaction_id' => time()   // Not set by Entity class
];


print_r(lic('querySelfExcludedSubject', [$data], $user));