<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');
$date = new DateTime($argv[3]);

$data = [
    'game_code' => 45014,
    'game_type' => 2,
    'central_system_session_id' => $argv[2] ?? 'M4F5D20155463EJL',
    'participation_id_code' => '',
    'progressive_participation_number' => 1,
    'participation_fee' => 100 * 100,
    'real_bonus_participation_fee' => 0,
    'participation_amount_resulting_play_bonus' => 0,
    'regional_code' => 3,
    'ip_address' => "192.198.0.1",
    'code_license_account_holder'  => 15427,
    'network_code'  => 14,
    'gambling_account' => (string)$user->data["id"],
    'player_pseudonym' => $user->data['username'],
    'date_participation' => [
        'date' => [
            'day' => $date->format('d'),
            'month' => $date->format('m'),
            'year' => $date->format('Y'),
        ],
        'time' => [
            'hour' => $date->format('h'),
            'minutes' => $date->format('i'),
            'seconds' => $date->format('s'),
        ],
    ],
    'initial_stage_progressive_number' => 1,
    'code_type_tag' => 4
];
var_dump($data);
var_dump(lic('acquisitionParticipationRightMessage', [$data], $user));