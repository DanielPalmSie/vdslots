<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

/**
 * Self exclusion types:
 *   1  Open ended
 *   2  30 days
 *   3  60 days
 *   4  90 days
 */

$data = [
    'tax_code' => 'RSSMLN62A01G224D',
    'self_exclusion_management' => '2', //1 for a self-exclusion and 2 for a reactivation
    'self_exclusion_type' => '0',   // needs to be 0 for reactivation
//    'transaction_id' => time()     // Not set by Entity class
];


print_r(lic('trasversalSelfExclusionManagement', [$data], $user));