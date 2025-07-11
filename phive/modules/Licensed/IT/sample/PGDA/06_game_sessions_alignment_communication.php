<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');
$date = new DateTime($argv[3]);

$data = [
    'game_code' => 45014,
    'game_type' => 2,
    'central_system_session_id' => $argv[2] ?? 'M4F5D201554626IU',
    'reference_date' => $date->format('dmY'), //ddmmyyyy
    'total_number_stages_played' => 1,
    'number_stages_completed' => 1,
    'round_up_list' => [
        [
            'license_code' => '15427',
            'total_amounts_waged' => 0,
            'total_amounts_returned' => 0,
            'total_taxable_amount' => 0,
            'total_mount_returned_resulting_jackpot' => 0,
            'total_mount_returned_resulting_additional_jackpot' => 0,
            'jackpot_amount' => 0,
            'total_amount_waged_real_bonuses' => 0,
            'total_amount_waged_play_bonuses' => 0,
            'total_amount_returned_real_bonuses' => 0,
            'total_amount_returned_play_bonuses' => 0,
        ]
    ]
];

var_dump($data);
var_dump(lic('gameSessionsAlignmentCommunication', [$data], $user));