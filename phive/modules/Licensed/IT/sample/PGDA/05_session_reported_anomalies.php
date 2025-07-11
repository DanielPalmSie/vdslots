<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');

$data = [
    'game_code' => 45014,
    'game_type' => 2,
    'request_id' => $argv[2] ?? 2020314868389,
];

var_dump($data);
var_dump(lic('sessionReportedAnomalies', [$data], $user));