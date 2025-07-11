<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');  // Double check this user has IT as country in your DB

$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => 'farnezi01',

];


print_r(lic('onAccountProvinceQuery', [$data], $user));







