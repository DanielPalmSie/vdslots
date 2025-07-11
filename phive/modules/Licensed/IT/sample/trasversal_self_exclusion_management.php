<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => $user->data['id'],
    'tax_code' => 'MRCTST85P17F205C',
    'self_exclusion_management' => '1', //1 for a self-exclusion and 2 for a reactivation
    'self_exclusion_type' => '1',
    'transaction_id' => time()
];


print_r(lic('trasversalSelfExclusionManagement', [$data], $user));