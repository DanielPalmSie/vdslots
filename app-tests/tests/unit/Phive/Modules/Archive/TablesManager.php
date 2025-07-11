<?php

namespace Tests\Unit\Phive\Modules\Archive;

class TablesManager
{
    public array $tables = [
        "bets",
        "bets_mp",
        "wins",
        "wins_mp",
        "tournaments",
        "tournament_entries",
        "actions",
        "micro_games",
    ];

    public function truncateTables($db): void
    {
        foreach ($this->tables as $table) {
            self::failQuery($db, "truncate {$table}");
        }
    }

    public function changeSchemaTables($db): void
    {
        $tables = [
//            'tournaments',
            'micro_games'
        ];
        foreach ($tables as $table) {
            self::failQuery($db, "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS schema_change_version VARCHAR(15);");
        }
    }

    public function revertSchemaTables($db): void
    {
        foreach ($this->tables as $table) {
            self::failQuery($db, "ALTER TABLE {$table} DROP IF EXISTS schema_change_version;");
        }
    }

    public function dropTables($db): void
    {
        foreach ($this->tables as $table) {
            self::failQuery($db, "drop table if exists {$table}");
        }
    }

    public function populateGlobal($db, $id, $u, $day): void
    {
        self::failQuery($db, "INSERT INTO tournaments (id, tpl_id, game_ref, tournament_name, category, start_format, win_format, play_format, cost, pot_cost, xspin_info, min_players, max_players, duration_minutes, mtt_start, end_time, start_time, status, mtt_reg_duration_minutes, mtt_late_reg_duration_minutes, prize_amount, guaranteed_prize_amount, prize_type, registered_players, created_at, max_bet, min_bet, house_fee, get_race, get_loyalty, get_trophy, rebuy_times, rebuy_cost, turnover_threshold, award_ladder_tag, duration_rebuy_minutes, ladder_tag, included_countries, excluded_countries, reg_wager_lim, reg_dep_lim, reg_lim_period, free_pot_cost, calc_prize_stamp, prizes_calculated, allow_bonus, total_cost, pause_calc, rebuy_house_fee, spin_m, pwd, number_of_jokers, bounty_award_id, bet_levels, desktop_or_mobile, reg_lim_excluded_countries) VALUES ({$day}, {$day}, 'netent_eldorado_not_mobile_sw', '€450 Guaranteed - Re-buy', 'normal', 'mtt', 'tht', 'xspin', 800, 0, '40', 2, 1000, 210, '2021-07-21 20:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'upcoming', 60, 180, 0, 45000, 'win-fixed', 0, '2022-10-{$day} 12:54:14', 20, 20, 200, 1, 0, 1, 1, 800, 0, '', 180, 'default_60', '', '', 0, 0, 0, 0, '0000-00-00 00:00:00', 0, 0, 2000, 0, 200, 5, '', 1, 0, '', 'desktop', '');");
        self::failQuery($db, "INSERT INTO micro_games (id, game_name, tag, sub_tag, game_id, languages, ext_game_name, client_id, module_id, width, height, popularity, game_url, meta_descr, bkg_pic, html_title, jackpot_contrib, op_fee, stretch_bkg, played_times, orion_name, device_type, operator, network, branded, active, blocked_countries, retired, device_type_num, payout_percent, min_bet, max_bet, ribbon_pic, enabled, volatility, num_lines, max_win, auto_spin, included_countries, multi_channel, mobile_id, blocked_logged_out, payout_extra_percent, blocked_provinces) VALUES ({$day}, 'Belle and the Beast {$day}', 'videoslots', '', 'nyxBelleandtheBeast{$day}', 'de,dgoj,en,es,fi,hi,it,ja,nl,no,pt,sv', 'nyx251279{$day}', 0, '', 0, 0, 0, '', '', 'html_belleandthebeast_.jpg', '', 0, 0.15, 1, 0, '', 'html5', 'High 5 Games', 'nyx', 0, 0, 'AF AS AR BS BH BD BE BA MM CI CU CY CD DA DK EG ES ER FJ FR GB GU HT VA HK IT IR IQ IL JO KZ KE KW KG LB LR LY LT MP MZ NG KP PK PS PR QA RE RW SA SN SG SO LK SD SY TZ TR UM VI UA AE US VE VN YE ZW RO DZ AO CG VU PG UG PA TR UM VI UA US VE VN YE TZ VA BG PT DE NL', 0, 1, 0.965, 50, 50000, 'newgameicon', 1, 8, 0, 2534, 1, '', 0, 0, '', 0, 'CA-ON');");

    }

    public function populateShards($db, $id, $u, $day): void
    {
        self::failQuery($db, "INSERT INTO actions (id, actor, target, descr, tag, created_at, actor_username) VALUES ({$id}, {$u}, {$u}, 'Cron set active to 1', 'active', '2022-10-{$day} 13:27:58', 'system');");
        self::failQuery($db, "INSERT INTO tournament_entries (id, t_id, user_id, cash_balance, spins_left, won_amount, result_place, play_format, win_amount, status, dname, win_format, get_race, get_loyalty, get_trophy, biggest_win, rebuy_times, rebuy_cost, turnover, updated_at, highest_score_at, site_id, joker, bounty) VALUES ({$id}, {$id}, {$u}, 4000, 200, 0, 0, 'xspin', 0, 'finished', 'HerrOber', 'tht', 1, 0, 1, 0, 0, 0, 0, '2022-10-{$day} 11:35:17', '0000-00-00 00:00:00', 1, 0, 0);");
        self::failQuery($db, "INSERT INTO bets (id, trans_id, amount, game_ref, user_id, created_at, mg_id, balance, bonus_bet, op_fee, jp_contrib, currency, device_type, loyalty) VALUES ({$id}, {$id}, 2000, 'playngo310', {$u}, '2022-10-{$day} 11:34:37', '{$id}', 998000, 0, 300, 0, 'SEK', 0, 11.5);");
        self::failQuery($db, "INSERT INTO bets_mp (id, t_id, trans_id, amount, game_ref, user_id, created_at, mg_id, balance, bonus_bet, op_fee, jp_contrib, currency, device_type, loyalty, e_id) VALUES ({$id}, {$id}, {$id}, 20, 'pragmatic_vs20olympgate', {$u}, '2022-10-{$day} 00:00:00', '{$id}', 240, 0, 3, 0, 'EUR', 0, 0, 62650702);");
        self::failQuery($db, "INSERT INTO wins (id, trans_id, game_ref, user_id, amount, created_at, mg_id, balance, award_type, bonus_bet, op_fee, currency, device_type) VALUES ({$id}, {$id}, 'nyx70513{$id}', {$u}, 5200, '2022-10-{$day} 00:00:00', '{$id}', 540579, 2, 0, 780, 'SEK', 1);");
        self::failQuery($db, "INSERT INTO wins_mp (id, t_id, trans_id, game_ref, user_id, amount, created_at, mg_id, balance, award_type, bonus_bet, op_fee, currency, device_type, e_id) VALUES ({$id}, {$id}, {$id}, 'pragmatic_vs20olympgate{$id}', {$u}, 5, '2022-10-{$day} 00:00:01', '{$id}', 7202, 2, 0, 0.75, 'EUR', 0, 62650792);");
    }

    public static function failQuery($db, $q): void
    {
        $db->query($q);

        if ($db->getHandle()->error) {
            dd2($db->getHandle()->error);
        }
    }

}
