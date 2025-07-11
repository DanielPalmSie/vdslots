<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');
$date = new DateTime($argv[4]);
$data = [
    'game_code' => 45014,
    'game_type' => 2,
    'session_id'  => $argv[2] ?? 'M4F5D20155463EJL',
    'initial_progressive_number' => 1,
    'last_progressive_number' => 1,
    'stage_date' => date('Ymd'),
    'flag_closing_day' => 0,
    'game_stages' => [
        [
            'total_taxable_amount' => 0,
            'stage_progressive_number' => 1,
            'datetime' => date('Ymdhis'),
            'players' => [
                [
                    'identifier' => $argv[3] ?? 'M4F5D20155463EJL',
                    'amount_available' => 100 * 100,
                    'amount_returned' => 0,
                    'bet_amount' => 0,
                    'taxable_amount' => 0,
                    'license_code' => 15427,
                    'jackpot_amount' => 0,
                    'amount_available_real_bonuses' => 0,
                    'amount_available_play_bonuses' => 0,
                    'amount_waged_real_bonuses' => 0,
                    'amount_staked_resulting_play_bonuses' => 0,
                    'amount_returned_real_bonuses' => 0,
                    'amount_returned_play_bonuses' => 0,
                    'amount_returned_resulting_jackpots' => 0,
                    'amount_returned_resulting_additional_jackpots' => 0,
                ],
            ],

        ]
    ]
];
var_dump($data);
var_dump(lic('gameExecutionCommunication', [$data], $user));