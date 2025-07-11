<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';


$user = cu($argv[1] ?? 'devtestit002');

$date = new DateTime($argv[4]);

$data = [
    'game_code' => 45014,
    'game_type' => 2,
    'central_system_session_id' => $argv[2] ?? 'M4F5D20155463EJL',
    'participation_id_code' => $argv[3] ?? 'M4F5D20155463EJL',
    'number_stage_undertaken_player' => 0,
    'participation_amount' => 100 * 100,
    'real_bonus_participation_amount' => 0,
    'play_bonus_participation_amount' => 0,
    'amount_staked' => 0,
    'real_bonus_staked_amount' => 0,
    'amount_staked_resulting_play_bonus' => 0,
    'taxable_amount' => 0,
    'amount_returned_winnings' => 0,
    'amount_returned_resulting_jackpots' => 0,
    'amount_returned_resulting_additional_jackpots' => 0,
    'amount_returned_assigned_as_real_bonus' => 0,
    'amount_giver_over_play_bonus' => 0,
    'code_license_account_holder' => 15427,
    'network_code' => 14,
    'gambling_account' => (string)$user->data["id"],
    'end_stage_progressive_number' => 1,
    'date_final_balance' => [
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
    'jackpot_fund_amount' => 0,
];

var_dump($data);
var_dump(lic('endParticipationFinalPlayerBalance', [$data], $user));