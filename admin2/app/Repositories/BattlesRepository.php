<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 9/13/16
 * Time: 6:46 PM
 */

namespace App\Repositories;

use App\Extensions\Database\FManager as DB;

class BattlesRepository
{
    public function getUserBattlesList($date_range, $user_id)
    {
        $end_date_sql = '';
        $query_parameters = ['user_id' => $user_id, 'start_date' => $date_range['start_date']];
        if (!empty($date_range['end_date'])) {
            $end_date_sql = "AND t.start_time <= :end_date";
            $query_parameters['end_date'] = $date_range['end_date'];
        }

        return DB::shSelect($user_id, 'tournament_entries', "SELECT
                                          t.id                                                          AS tournament_id,
                                          te.id                                                         AS entry_id,
                                          t.tournament_name                                             AS t_name,
                                          mg.game_name                                                  AS game,
                                          t.category                                                    AS battle_type,
                                          te.won_amount                                                 AS won_amount,
                                          te.rebuy_times                                                AS rebuy_times,
                                          te.rebuy_cost                                                 AS rebuy_cost,
                                          te.spins_left                                                 AS spin_left,
                                          t.xspin_info * t.spin_m                                       AS total_spins,
                                          100 - ROUND(te.spins_left * 100 / t.xspin_info * t.spin_m, 2) AS spin_ratio,
                                          te.result_place                                               AS result_place,
                                          t.start_time                                                  AS t_start,
                                          t.end_time                                                    AS t_end,
                                          te.status                                                     AS te_status
                                        FROM tournament_entries te
                                          LEFT JOIN users u
                                            ON u.id = te.user_id
                                          LEFT JOIN tournaments t
                                            ON te.t_id = t.id
                                          LEFT JOIN micro_games mg
                                            ON mg.ext_game_name = t.game_ref
                                        WHERE te.user_id = :user_id
                                        AND t.start_time > :start_date $end_date_sql GROUP BY entry_id", $query_parameters);
    }
}