<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu(6304200);  // Double check this user has IT as country in your DB
if (empty($user))
    die("no user");
$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => uid($user),

];


print_r(lic('onAccountDocumentQuery', [$data], $user));







