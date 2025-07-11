<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';


$user = cu($argv[1] ?? 'devtestit002');
$date1 = new DateTime($argv[2]);
$date2 = new DateTime($argv[3]);

$license_session_id = time();
print_r($license_session_id);
echo PHP_EOL;

$data = [
    'game_code' => 45014,
    'game_type' => 2,
    'license_session_id' => $license_session_id,
    'start_date_session' => [
        'date' => [
            'day' => $date1->format('d'),
            'month' => $date1->format('m'),
            'year' => $date1->format('Y'),
        ],
        'time' => [
            'hour' => $date1->format('h'),
            'minutes' => $date1->format('i'),
            'seconds' => $date1->format('s'),
        ],
    ],
    'end_date_session' => [
        'day' => $date2->format('d'),
        'month' => $date2->format('m'),
        'year' => $date2->format('Y'),
    ],
    //'attributes_session_list' => [
    //    [
    //        'code' => \IT\Pgda\Type\AttributesSessionType::BONUS,
    //        'value' => 'F',
    //    ],[
    //        'code' => \IT\Pgda\Type\AttributesSessionType::JACKPOT_INTERNAL,
    //        'value' => '1',
    //    ]
    //]
];

var_dump($data);
var_dump(lic('startGameSessions', [$data], $user));