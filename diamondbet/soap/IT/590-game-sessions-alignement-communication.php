<?php

use IT\Services\AAMSSession\GameSessionSummary;

require_once '/var/www/videoslots.it/phive/phive.php';
$ext_session_id = $argv[1] ?? 'M4F852015A52CAGL';
$username = $argv[2] ?? 6624618;
$user = cu($username);

$sql = "SELECT
ext_game_participations.user_id,
ext_game_participations.ext_game_id,
ext_game_participations.ext_session_id,
ext_game_participations.created_at,
ext_game_participations.ended_at,
ext_game_participations.balance,
ext_game_participations.stake,
MAX(ext_game_participations_increments.stake_balance_real_bonus) AS has_real_bonus,
MAX(ext_game_participations_increments.stake_balance_play_bonus) AS has_play_bonus,
users_game_sessions.bet_cnt AS bet_count,
bet_amount AS bet,
win_amount AS win,
bet_amount - win_amount AS net
FROM ext_game_participations
LEFT JOIN ext_game_participations_increments ON ext_game_participations.participation_id = ext_game_participations_increments.participation_id
LEFT JOIN users_game_sessions ON SUBSTRING_INDEX(ext_game_participations.ext_id, '-', -1) = users_game_sessions.id
WHERE ext_session_id = '{$ext_session_id}'";
$ext_game_participation = phive('SQL')->sh($user)->loadAssoc($sql);
$game_country_version   = phive('SQL')->loadAssoc(NULL, 'game_country_versions', ['ext_game_id' => $ext_game_participation['ext_game_id']]);
$gss  = new GameSessionSummary();
$amount_returned_as_real_bonus = $gss->getAmountReturnedAssignedAsRealBonus( $user->getId(), $ext_game_participation['created_at'], $ext_game_participation['ended_at']);

$user = cu($ext_game_participation['user_id']);
if($ext_game_participation['has_play_bonus']) {
    $bonus = phive('SQL')->sh($user)->loadAssoc("SELECT
	    SUM(IFNULL(wins.amount, 0)) AS win
        FROM wins
        WHERE
            wins.user_id = {$ext_game_participation['user_id']}
            AND wins.game_ref = '{$ext_game_participation['ext_game_id']}'
            AND wins.created_at BETWEEN '{$ext_game_participation['created_at']}' AND '{$ext_game_participation['ended_at']}'
            ORDER BY wins.id ASC;
    ");
    $ext_game_participation['bet_count'] = 1;
    $ext_game_participation['bet'] = $ext_game_participation['stake'];
    $ext_game_participation['win'] = $ext_game_participation['win'];
    $ext_game_participation['net'] = 0; // $ext_game_participation['bet'] - $ext_game_participation['win']
}
$data = [
    'game_code' => $game_country_version['game_regulatory_code'],
    'game_type' => 2,
    'central_system_session_id'  => $ext_game_participation['ext_session_id'],
    'reference_date'             => date('dmY', strtotime($ext_game_participation['created_at'])),
    'total_number_stages_played' => $ext_game_participation['bet_count'],
    'number_stages_completed'    => $ext_game_participation['bet_count'],
    'round_up_list' => [
        [
            'license_code'           => '15427',
            'total_amounts_waged'    => $ext_game_participation['bet'],
            'total_amounts_returned' => $ext_game_participation['win'] + $amount_returned_as_real_bonus,
            'total_taxable_amount'   => $ext_game_participation['net'] - $amount_returned_as_real_bonus,
            'total_mount_returned_resulting_jackpot' => 0,
            'total_mount_returned_resulting_additional_jackpot' => 0,
            'jackpot_amount'                     => 0,
            'total_amount_waged_real_bonuses'    => 0,
            'total_amount_waged_play_bonuses'    => $ext_game_participation['has_play_bonus'] ? $ext_game_participation['bet'] : 0,
            'total_amount_returned_real_bonuses' => $amount_returned_as_real_bonus,
            'total_amount_returned_play_bonuses' => $ext_game_participation['has_play_bonus'] ? $ext_game_participation['win'] : 0,
        ]
    ]
];

print_r($data);
//die;
var_dump(lic('gameSessionsAlignmentCommunication', [$data], $user));
