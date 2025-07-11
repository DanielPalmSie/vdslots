<?php
/**
 * Message 580
 *
 * This script accept ext_session_id parameter that is used to collect all the information of the game session
 * and send back to sogei/aams
 *
 * php 580-game-execution-communication.php M4F7E20159434CQH
 */

use IT\Services\AAMSSession\GameSessionSummary;

require_once '/var/www/videoslots.it/phive/phive.php';
$ext_session_id = $argv[1] ?? 'M4F852015A52CAGL';
$username = $argv[2] ?? 6624618;
$user = cu($username);

$ext_game_participation = phive('SQL')->sh($user)->loadAssoc("SELECT *,
MAX(ext_game_participations_increments.stake_balance_real_bonus) AS has_real_bonus,
MAX(ext_game_participations_increments.stake_balance_play_bonus) AS has_play_bonus
FROM ext_game_participations
LEFT JOIN ext_game_participations_increments ON ext_game_participations.participation_id = ext_game_participations_increments.participation_id
WHERE ext_session_id = '{$ext_session_id}'");

// getting ext_game_participation record using `ext_session_id`
//$ext_game_participation = phive('SQL')->loadAssoc(NULL, 'ext_game_participations', ['ext_session_id' => $ext_session_id]);
// getting the game_country_version record using `ext_session_id` (NB we cannot join cause mixing collation)
$game_country_version   = phive('SQL')->loadAssoc(NULL, 'game_country_versions', ['ext_game_id' => $ext_game_participation['ext_game_id']]);
// message 580 can involve many users/players so this must be fixed to work with multiplayer
$user = cu($ext_game_participation['user_id']);
// retrieving stages/round/spins collection from db
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
    $stages = [
        [
            'created_at'  => $ext_game_participation['created_at'],
            'bet_balance' => $ext_game_participation['stake'],
            'bet'         => $ext_game_participation['stake'],
            'win'         => $bonus['win'],
            'net'         => 0 // $ext_game_participation['stake'] - $bonus['win']
        ]
    ];
} else {
    $sql = "SELECT
        bets.created_at AS created_at,
        (bets.balance + bets.amount) AS bet_balance,
        bets.amount AS bet,
        bets.bonus_bet AS bet_bonus,
        (bets.balance + IFNULL(wins.amount, 0)) AS win_balance,
        IFNULL(wins.amount, 0) AS win,
        IFNULL(wins.bonus_bet, 0) AS win_bonus,
        (bets.amount - IFNULL(wins.amount, 0)) * 10000 AS net
        FROM bets LEFT JOIN wins ON bets.trans_id = wins.trans_id
        WHERE
            bets.user_id = {$ext_game_participation['user_id']}
            AND bets.game_ref = '{$ext_game_participation['ext_game_id']}'
            AND bets.created_at BETWEEN '{$ext_game_participation['created_at']}' AND '{$ext_game_participation['ended_at']}'
        ORDER BY bets.id ASC";
    echo $sql .PHP_EOL;
    $stages = phive('SQL')->sh($user)->loadArray($sql);
}

// chunking collection
$chunks = array_chunk($stages, 1000);
$counter = 1;
foreach($chunks as $stages) {
    // generating 580 payload
    $data = [
        'game_code' => $game_country_version['game_regulatory_code'],
        'game_type' => 2,
        'session_id' => $ext_session_id,
        'initial_progressive_number' => $counter,
        'last_progressive_number' => $counter + count($stages) - 1,
        'stage_date' => date('Ymd', strtotime($ext_game_participation['created_at'])),
        'flag_closing_day' => 1,
        'game_stages' => []
    ];
    foreach ($stages as $i => $stage) {
        $data['game_stages'][] = [
            'total_taxable_amount' => $stage['net'],
            'stage_progressive_number' => $counter,
            'datetime' => date('YmdHis', strtotime($stage['created_at'])),
            'players' => [
                [
                    'identifier' => $ext_game_participation['participation_id'],
                    'amount_available' => $stage['bet_balance'] + ( $i !== 0 ? $stages[$i-1]['win'] : 0),
                    'amount_returned'  => $stage['win'],
                    'bet_amount'       => $stage['bet'],
                    'taxable_amount'   => $stage['net'],
                    'license_code'     => 15427,
                    'jackpot_amount'   => 0,
                    'amount_available_real_bonuses' => 0,
                    'amount_available_play_bonuses' => $ext_game_participation['has_play_bonus'] ? $stage['bet_balance'] : 0,
                    'amount_waged_real_bonuses'     => 0,
                    'amount_staked_resulting_play_bonuses' => $ext_game_participation['has_play_bonus'] ? $stage['bet'] : 0,
                    'amount_returned_real_bonuses' => 0,
                    'amount_returned_play_bonuses' => $ext_game_participation['has_play_bonus'] ? $stage['win'] : 0,
                    'amount_returned_resulting_jackpots' => 0,
                    'amount_returned_resulting_additional_jackpots' => 0,
                ]
            ]
        ];
        $counter++;
    }

    $gss  = new GameSessionSummary();
    $amount_returned_as_real_bonus = $gss->getAmountReturnedAssignedAsRealBonus( $user->getId(), $ext_game_participation['created_at'], $ext_game_participation['ended_at']);
      if ($amount_returned_as_real_bonus > 0) {
//          echo ((int)$data['game_stages'][0]['total_taxable_amount']) . PHP_EOL;
//          echo (int)$amount_returned_as_real_bonus * 10000 . PHP_EOL;
         $taxable_amount = (int)$data['game_stages'][0]['total_taxable_amount'] - (int)$amount_returned_as_real_bonus * 10000;
//          echo $taxable_amount . PHP_EOL;
        $data['game_stages'][0]['total_taxable_amount'] = $taxable_amount;
        $data['game_stages'][0]['players'][0]['amount_returned_real_bonuses'] += $amount_returned_as_real_bonus;
        $data['game_stages'][0]['players'][0]['amount_returned'] += $amount_returned_as_real_bonus;
        $data['game_stages'][0]['players'][0]['taxable_amount'] = $taxable_amount;
    }


    // FOR EMPTY STAGES
    if (empty($data)) {
        $data = [
            'game_code' => $game_country_version['game_regulatory_code'],
            'game_type' => 2,
            'session_id' => $ext_session_id,
            'initial_progressive_number' => 1,
            'last_progressive_number' => 1,
            'stage_date' => date('Ymd', strtotime($ext_game_participation['created_at'])),
            'flag_closing_day' => 1,
            'game_stages' => []
        ];
    }
    // sending message
    //print_r($data);
    print_r($data);
//    die();
    var_dump(lic('gameExecutionCommunication', [$data], $user));
}
