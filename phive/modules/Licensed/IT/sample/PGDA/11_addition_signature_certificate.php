<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');

$data = [
    'game_code' => 0,
    'game_type' => 0,
    'certificate_serial_number' => 'idunno',
    'certificate' => 'idunno',
];

var_dump($data);
var_dump(lic('additionSignatureCertificate', [$data], $user));