<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => $user->data['id'],
    'status' => '1', //[1 => Open, 2 => Suspended, 3 => Closed, 4 => Dormant, 5 => Blocked]
    'reason' => '3', /* requested by [1 => ADM,
                                      2 => Licensee,
                                      3 => account holder,
                                      4 => Judicial Authority,
                                      5 => ADM following the decease of the holder,
                                      6 => Licensee due to failure to send the ID document
                                      7 => Licensee due to a suspected fraud, forms of collusion or the use of the account by third parties
                                      8 => the account owner for reasons of self-exclusion] */
    'transaction_id' => time(),
];

print_r(lic('changeAccountStatus', [$data], $user));



