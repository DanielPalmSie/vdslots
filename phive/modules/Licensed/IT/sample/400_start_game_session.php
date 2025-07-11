<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'license_session_id' => (string)time(), // id stored in our table ext_game_participations
    'start_date_session' => [
        'date' => [
            'day' => date('d'),
            'month' => date('m'),
            'year' => date('Y')
        ],
        'time' => [
            'hours' => date('H'),
            'minutes' => date('i'),
            'seconds' => date('s')
        ]
    ],
    //“presumed’ game session end (which is understood as being valid up to 12 am of the day indicate)
    // should be far in the future to be able to close all sessions before that date
    'end_date_session' => [
        'day' => date('d'), // use carbon +x days (Carbon::now()->addDays($duration);)
        'month' => date('m'),// use carbon +x days
        'year' => date('Y')// use carbon +x days
    ],
    'attributes_session_list' => [  // All the bonuses being present on the account
        [
            'code' => 'MNI',
            'value' => '10',
        ],
        [
            'code' => 'MNI',
            'value' => '999999',
        ],
        [
            'code' => 'JK2',
            'value' => '1',
        ],
    ],
];
/**
 *  session attr     value       Node
 *  JK1             0/1         session with jackpot internal to the game; value 1 if this type of jackpot is foreseen
 *  JK2             0/1         session with additional jackpots; value 1 if this type of jackpot is foreseen
 *  BON             B/F         B = session with bonus   /   F = session with fun bonus
 *  MNI                         Minimum amount foreseen to “join” the table (in euro cents)
 *  MXI                         Maximum amount foreseen to “join” the table (in euro cents)
 */


print_r(lic('startGameSession', [$data], $user));




