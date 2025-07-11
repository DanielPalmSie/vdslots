<?php

use Videoslots\MicroGames\Services\GamesFilterService;

require_once __DIR__ . '/../../api/ExtModule.php';


class MicroGames extends ExtModule{
    public $handle_redirect_url = false;

    function __construct(){
        $this->table = 'micro_games';
        parent::__construct();
        $this->db = phive('SQL');
        $this->rtp_start_stamp = '2017-07-01 00:00:00';
    }

    function table(){
        return $this->table;
    }

    public function getGameIdByCol($u_obj, $id_col, $id_value, $where_device = ''){
        $jur = $this->getCountryForOverride($u_obj);

        $str = "SELECT game_id FROM game_country_overrides WHERE $id_col = '$id_value' AND country IN('$jur', 'ALL') {$where_device} LIMIT 1";

        return phive('SQL')->getValue($str);
    }

    public function getGameIdByLaunchId($u_obj, $launch_id, $where_device = ''){
        return $this->getGameIdByCol($u_obj, 'ext_launch_id', $launch_id, $where_device);
    }

    public function getGameIdByExtId($u_obj, $ext_id, $where_device = ''){
        return $this->getGameIdByCol($u_obj, 'ext_game_id', $ext_id, $where_device);
    }

    function overrideGameForTournaments($u_obj, $g)
    {
        $networkModule = phive('MicroGames')->getNetworkModule($g);
        $iso = $networkModule->getLicSetting('bos-country', $u_obj);

        return $this->overrideGame($u_obj, $g, $iso, !empty($iso));
    }

    public function getCountryForOverride($u_obj = null)
    {
        $licensed_countries = phive('Licensed')->getSetting('licensed_countries', []);
        $non_jur_countries = phive('MicroGames')->getSetting('game_override_extra_countries', []);
        $all_countries = array_merge($licensed_countries, $non_jur_countries);

        $user_country = phive('Licensed')->getLicCountryProvince(cu($u_obj));
        if (in_array($user_country, $all_countries)) {
            return $user_country;
        } else {
            return licJur($u_obj);
        }
    }

    /**
     * Optimized query for situations when we only need the original game ref if overridden.
     *
     * This is not to be used on a web context and only for game play, user must be present, otherwise we return what
     * we got.
     *
     * @param $external_game_id. The game ref.
     * @param $user
     * @param string|null $country. Optional country to use instead of the user jurisdiction when locating the override.
     * Used for tournament bos-country. For game providers which do not implement bos-country this defaults to the
     * original behaviour and uses the user jurisdiction.
     * @return mixed The original game ref (db.micro_games.ext_game_name) if overridden else the game ref parameter.
     * @example getOriginalRefIfOverridden('mgs_mermaidsmillions_94', cu(), getLicSetting('bos-country'))
     */
    public function getOriginalRefIfOverridden($external_game_id, $user, string $country = null)
    {
        if (empty($user)) {
            return $external_game_id;
        }

        /**
         * For tournament overrides we look first for bos-country config ('IE') then for default jurisdiction ('MT').
         */
        if (!empty($country)) {
            $gco = $this->getGameCountryOverrideByOverride($user, $external_game_id, $country, true);
            if (empty($gco)) {
                return $external_game_id;
            }
            $game = $this->getById($gco['game_id']);
            return $game['ext_game_name'] ?? $external_game_id;
        }

        $country_or_jur = $this->getCountryForOverride($user);

        $overridden_ref = phive('SQL')->getValue("SELECT mg.ext_game_name FROM game_country_overrides gco
                                        LEFT JOIN micro_games mg on gco.game_id = mg.id
                                        WHERE gco.ext_game_id = '{$external_game_id}'
                                          AND gco.country IN('$country_or_jur', 'ALL') LIMIT 1");

        return $overridden_ref ?: $external_game_id;
    }

    /**
     * Returns the original game row for the external game ref.
     * The optional <b>device_type</b> parameter can be used to select the correct game if
     * the game ref or override is identical for multiple device types.
     *
     * @param string $game_ref <p>
     * A game ref or an overriding game ref.
     * </p>
     * @param mixed|null $user <p>
     * The user object or user id.
     * </p>
     * @param string|null $country [optional] <p>
     * If given then looks for an override for this country. This can be used for tournament bos-country for example.
     * If not given or if no override exists for <b>country</b> then looks for an override for the user's jurisdiction.
     * </p>
     * @param string|null $device_type [optional] <p>
     * The device type ('flash', 'html5', 'android' etc) or device type num (0, 1, 2 etc).
     * </p>
     * @return array|null The database row of the original game. If an override was found the returned game
     * contains 2 helper fields with details of the override: 'overriding_ext_game_name' and 'overriding_country'.
     */
    public function getOriginalGame(string $game_ref, $user = null, string $country = null, string $device_type = null): ?array
    {
        $game = null;
        $gco = $this->getGameCountryOverrideByOverride($user, $game_ref, $country, !empty($country), $device_type);
        if (!empty($gco)) {
            $game = $this->getById($gco['game_id']);
            $game['overriding_ext_game_name'] = $gco['ext_game_id'];
            $game['overriding_country'] = $gco['country'];
        }

        if (empty($game)) {
            $sql = "SELECT * FROM micro_games WHERE ext_game_name = " . phive('SQL')->escape($game_ref);
            if (($device_type !== null) && ($device_type !== '') && ($device_type !== false)) {
                $col = is_numeric($device_type) ? 'device_type_num' : 'device_type';
                $sql .= " AND {$col} = " . phive('SQL')->escape($device_type);
            }
            $game = phive('SQL')->loadAssoc($sql);
        }

        if (empty($game)) {
            return null;
        }

        $module = phive('Casino')->getNetworkName($game['network']);
        if (!empty($module)) {
            $log = array_merge(['original_id' => $game['id'], 'original_game_ref' => $game['ext_game_name']], compact('game_ref', 'device_type'));
            phive($module)->dumpTst(strtolower($module) . '_getOriginalGame', $log, uid($user));
        }

        return $game;
    }

    /**
     * Returns the matching db.game_country_overrides row.
     *
     * @param DBUser $u_obj
     * @param array $g
     * @param string $iso
     * @param bool $force_iso. If true this method looks first for an override for $iso regardless of config settings,
     * then for an override where $iso matches the config settings.
     * @return array mixed
     */
    public function getGameOverride($u_obj, $g, $iso, bool $force_iso = false)
    {
        if (empty($g['id'])) {
            return $g;
        }

        // Returns this override first if it exists and we are forcing $iso.
        if ($force_iso && !empty($iso)) {
            $c = phive('SQL')->escape($iso);
            $str = "SELECT * FROM game_country_overrides WHERE game_id = {$g['id']} AND country IN ({$c}, 'ALL') LIMIT 1";
            $row = phive('SQL')->loadAssoc($str);
            if (!empty($row)) {
                return $row;
            }
        }

        if (empty($iso)) {
            $country_or_jur = $this->getCountryForOverride($u_obj);
        } else {
            if (in_array($iso, phive('MicroGames')->getSetting('game_override_extra_countries', []))) {
                $country_or_jur = $iso;
            } else {
                $country_or_jur = licJurFromCountry($iso);
            }
        }

        $str = "SELECT * FROM game_country_overrides WHERE game_id = {$g['id']} AND country IN('$country_or_jur', 'ALL') LIMIT 1";
        return phive('SQL')->loadAssoc($str);
    }

    /**
     * Returns the db.game_country_overrides row for the overriding game ref, or null if there is no override.
     *
     * @param $user. The user.
     * @param string $game_ref. The game ref of the override.
     * @param string|null $iso. The iso to use for the override, or null to use the user's jurisdiction.
     * @param bool $force_iso. If true this method looks first for an override for $iso regardless of config settings,
     * then for an override where $iso matches the config settings.
     * @param string|null $device_type [optional] <p>
     * The device type ('flash', 'html5', 'android' etc) or device type num (0, 1, 2 etc).
     * </p>
     * @return array|null The db.game_country_overrides row or null if there is no override.
     */
    public function getGameCountryOverrideByOverride($user, string $game_ref, string $iso = null, bool $force_iso = false, string $device_type = null)
    {
        $g_ref = phive('SQL')->escape($game_ref);
        $where_device = '';
        if (($device_type !== null) && ($device_type !== '') && ($device_type !== false)) {
            $col = is_numeric($device_type) ? 'device_type_num' : 'device_type';
            $where_device = "AND {$col} = " . phive('SQL')->escape($device_type);
        }

        // Returns this override first if it exists and we are forcing $iso.
        if ($force_iso && !empty($iso)) {
            $c = phive('SQL')->escape($iso);
            $s = "SELECT * FROM game_country_overrides WHERE (ext_game_id = {$g_ref} OR ext_launch_id = {$g_ref}) AND country IN ({$c}, 'ALL') {$where_device} LIMIT 1";
            $row = phive('SQL')->loadAssoc($s);
            if (!empty($row)) {
                return $row;
            }
        }

        if (empty($iso)) {
            $country_or_jur = $this->getCountryForOverride($user);
        } else {
            if (in_array($iso, $this->getSetting('game_override_extra_countries', []))) {
                $country_or_jur = $iso;
            } else {
                $country_or_jur = licJurFromCountry($iso);
            }
        }

        $s = "SELECT * FROM game_country_overrides WHERE (ext_game_id = {$g_ref} OR ext_launch_id = {$g_ref}) AND country IN ('{$country_or_jur}', 'ALL') {$where_device} LIMIT 1";
        $row = phive('SQL')->loadAssoc($s);
        return empty($row) ? null : $row;
    }

    /**
     * We only override the game ref and not the whole game as @see overrideGame
     *
     * @param $u_obj
     * @param $game_ref
     * @return mixed
     */
    public function overrideGameRef($u_obj, $game_ref)
    {
        $country_or_jur = $this->getCountryForOverride($u_obj);

        $overridden_ref = phive('SQL')->getValue("SELECT gco.ext_game_id FROM micro_games mg
                        LEFT JOIN game_country_overrides gco ON gco.game_id = mg.id
                        WHERE mg.ext_game_name = '{$game_ref}' AND country IN('$country_or_jur', 'ALL') LIMIT 1");

        return $overridden_ref ?: $game_ref;
    }

    /**
     * @param DBUser $u_obj
     * @param $g
     * @param string $iso
     * @param bool $force_iso. If true this method looks first for an override for $iso regardless of config settings,
     * then for an override where $iso matches the config settings.
     * @return mixed
     */
    public function overrideGame($u_obj, $g, $iso = '', bool $force_iso = false)
    {
        // We don't try and override twice.
        if($g['overridden'] === true){
            return $g;
        }
        $override = $this->getGameOverride($u_obj, $g, $iso, $force_iso);
        // If there is no override we just return the game as is as there is nothing to override with.
        if(empty($override)){
            return $g;
        }
        $map = [
            'ext_game_name'        => 'ext_game_id',
            'game_id'              => 'ext_launch_id',
            'payout_extra_percent' => 'payout_extra_percent',
            'payout_percent'       => 'payout_percent'
        ];
        // We don't override with empty values.
        $res = phive()->mapit($map, $override, $g, false);
        // In case ext_launch_id is empty we just copy the ext_game_name, works for all GPs except MicroGaming.
        if(empty($res['game_id'])){
            $res['game_id'] = $res['ext_game_name'];
        }
        $res['overridden'] = true;
        $res['overriding_country'] = $override['country'];
        $res['original_game_id'] = $g['game_id'];
        $res['original_ext_game_name'] = $g['ext_game_name'];
        return $res;
    }

    function getOpenGameSessions($group_col = ''){
        $zdate = phive()->getZeroDate();
        if(!empty($group_col)){
            $group_by = "GROUP BY $group_col";
        }
        return $this->db->shs()->loadArray("SELECT * FROM users_game_sessions WHERE end_time = '$zdate' $group_by");
    }

    function getDesktopGame($cg, ?bool $active = true){
        //We have mobile game
        $cg['id'] = intval($cg['id']);
        if (!empty($cg['device_type_num'])) {
            $sql = "SELECT * FROM micro_games WHERE mobile_id = {$cg['id']}";
            if (!is_null($active)) {
                $sql .= " AND active = " . (int)$active;
            }
            return $this->db->readOnly()->loadAssoc($sql);
        }
        return $cg;
    }

    function getMobileGame($cg){
        //We have a desktop game
        if (empty($cg['device_type_num']))
            return $this->getById($cg['mobile_id']);
        return $cg;
    }

    //Returns the correct game irrespective if $cg is a mobile or desktop game, only works with CGI mode
    function getCurrentGame($cg){
        if(!phive()->isMobile())
            return $this->getDesktopGame($cg);
        return $this->getMobileGame($cg);
    }


    function getGames($game){
        return [
            $this->getDesktopGame($game),
            $this->getMobileGame($game),
        ];
    }


    private function ratio($a, $b)
    {
        $_a = $a;
        $_b = $b;

        while ($_b != 0) {
            $remainder = $_a % $_b;
            $_a = $_b;
            $_b = $remainder;
        }

        $gcd = abs($_a);

        $_a_result = $a / $gcd;
        $_b_result = $b / $gcd;

        $rr = $_a_result / $_b_result;

        if (is_int($rr * 100)) {
            $_a_result = $rr;
            $_b_result = 1;
        }
        else {
            $cnt = 1;
            while ($rr > 10) {
                $rr = ($rr / 10);
                $cnt = $cnt * 10;
            }
            $_a_result = round($rr * 10, 2);
            $_b_result = (10 / $cnt);
        }
        //$_a_result = round($rr * 10, 2).'-10';
        /*
        if ($_a_result > $_b_result) {
            $_a_result = round($rr, 2);
            $_a_result = $_a_result * 10;
            $_b_result = 10;
        }
        else if ($_a_result < $_b_result) {
            $_a_result = round($rr * 10, 2);
            //$_a_result *= 10;
            $_b_result = 10;
        }
        */

        $result = $_a_result.' / '.$_b_result;

        return trim($result) == '/' ? '0 / 0' : $result;
    }

    function getHitRate($user_id, $date1, $date2, $game_ref, $session_id = 0)
    {
        $base_sql = "SELECT SUM(bet_cnt) AS bets_total, SUM(win_cnt) AS wins_total FROM `users_game_sessions` WHERE start_time > '{$this->rtp_start_stamp}' AND ";
        if(empty($session_id)){
            $sql_str = sprintf("$base_sql user_id = %d AND start_time BETWEEN '%s' AND '%s' AND game_ref='%s'", $user_id, $date1, $date2, $game_ref);
        }else{
            $sql_str = sprintf("$base_sql id='%s'", $session_id);
        }

        $hit_rate = phive('SQL')->sh($user_id)->loadAssoc($sql_str);

        if (!empty($hit_rate) && !empty($hit_rate['bets_total'])) {
            return $this->ratio($hit_rate['wins_total'], $hit_rate['bets_total']);
        }

        return 'N / A';
    }

    function getBiggestWin($user_id, $game_ref, $date1, $date2){
        $user_id     = (int)$user_id;
        $biggest_win = ['-', '-'];
        $win_sql     = sprintf("SELECT amount as amount, currency, created_at
            FROM wins
            WHERE user_id = %d
                AND game_ref = '%s'
                AND created_at >= '%s'
                AND created_at <= '%s'
            ORDER BY amount desc, created_at DESC
            LIMIT 1;", $user_id, $game_ref, $date1, $date2);

        $win_result = $this->db->readOnly()->sh($user_id)->loadAssoc($win_sql);

        $this->db->prependFromNodeArchive($win_result, $user_id, $date1, $win_sql, 'wins', 'loadAssoc');

        $win_result = phive()->max2d($win_result, 'amount');

        if (!empty($win_result['amount'])){
            $biggest_win[0] = phive()->twoDec($win_result['amount']).' '.$win_result['currency'];

            $bet_sql = sprintf("
                SELECT amount, currency, created_at
                FROM bets
                WHERE user_id = %d
                AND game_ref = '%s'
                AND created_at <= '%s'
                ORDER BY created_at
                DESC LIMIT 1", $user_id, $game_ref, $win_result['created_at']);

            $bet_result = $this->db->readOnly()->sh($user_id)->loadAssoc($bet_sql);
            if (empty($bet_result)) {
                $this->db->prependFromNodeArchive($bet_result, $user_id, $date1, $bet_sql, 'bets', 'loadAssoc');
            }
            $bet_result = phive()->max2d($bet_result, 'amount');

            if (!empty($bet_result['amount'])){
                $biggest_win[1] = phive()->twoDec($bet_result['amount']).' '.$bet_result['currency'];
            }
        }

        return $biggest_win;
    }

    function getPayoutRatio($game_ref)
    {
        $str = sprintf("SELECT (SUM(gmc.win_sum) / SUM(gmc.bet_sum)) AS payout_ratio, gmc.game_ref FROM game_month_cache gmc, micro_games mg
                        WHERE mg.ext_game_name = gmc.game_ref
                        AND gmc.game_ref = '%s'", $game_ref);

        $res = phQget($str);
        if (!empty($res)) {
            return round($res['payout_ratio'] * 100, 1);
        }

        $res = phive("SQL")->loadAssoc($str);
        phQset($str, $res, 36000);
        return round($res['payout_ratio'] * 100, 1);
    }

    // TODO Henrik: go through all user input and make sure it's cleaned up to avoid SQL injection.
    function rtpGetGraph($user, $gid, $rtp = 96, $dates = false, $type = 'day')
    {
        $ud       = ud($user);
        $currency = $ud['currency'];
        $gid      = (int)$gid;
        $game     = $this->getById($gid);
        $game_ref = $game['ext_game_name'];
        $debug    = '';
        $user_id  = $ud['id'];
        $hisfmt   = 'Y-m-d H:i:s';

        if ($dates !== false && DateTime::createFromFormat('Y-m-j H:i:s', $dates[0]) !== false && DateTime::createFromFormat('Y-m-j H:i:s', $dates[1]) !== false) {
            $date1 = new DateTime($dates[0]);
            $date2 = new DateTime($dates[1]);
        }
        else {
            $date1 = new DateTime("-1 month");
            $date2 = new DateTime();
        }

        $diff   = $date2->diff($date1)->format("%a");
        $result = [];
        $wins   = $bets = [];

        $wins_total = $bets_total = 0;

        for ($i = 0; $i <= $diff; $i++) {

            $stamp = $date1->format("Y-m-d").' +'.$i.'days';
            $ts = strtotime($stamp);
            $date = date("Y-m-d", $ts);

            $sql_str = sprintf("SELECT SUM(win_amount) AS wins_sum, SUM(bet_amount) AS bets_sum, (SUM(win_amount) * 100 / SUM(bet_amount)) AS rtp, start_time
                                FROM users_game_sessions
                                WHERE user_id = '%d'
                                AND game_ref = '%s'
                                AND start_time > '{$this->rtp_start_stamp}'
                                AND DATE(start_time) = '%s'", $user_id, $game_ref, $date);

            $res = phive('SQL')->sh($user_id)->loadAssoc($sql_str);

            $wins_total += (!empty($res) && $res['wins_sum'] != NULL) ? $res['wins_sum'] : 0;
            $bets_total += (!empty($res) && $res['bets_sum'] != NULL) ? $res['bets_sum'] : 0;

            if ($type == 'week') {

                $week         = date("yW", $ts);
                $wins[$week] += !empty($res) ? $res['wins_sum'] : 0;
                $bets[$week] += !empty($res) ? $res['bets_sum'] : 0;
                $rtp_counted  = ($bets[$week] > 0) ? $wins[$week] * 100 / $bets[$week] : 0;
                $rtp_val      = !empty($rtp_counted) ? phive()->decimal($rtp_counted) : $rtp;
                $result[$week] = [$ts * 1000, !is_numeric($rtp_val) ? 0 : $rtp_val];

            } else if ($type == 'month') {

                $month          = date("n.Y", $ts);
                $wins[$month]  += !empty($res) ? $res['wins_sum'] : 0;
                $bets[$month]  += !empty($res) ? $res['bets_sum'] : 0;
                $rtp_counted    = ($bets[$month] > 0) ? $wins[$month] * 100 / $bets[$month] : 0;
                $rtp_val        = !empty($rtp_counted) ? number_format($rtp_counted, 2, '.', '') : $rtp;
                $result[$month] = [$ts * 1000, !is_numeric($rtp_val) ? 0 : $rtp_val];

            } else {
                $result[] = [
                    $ts * 1000, // 1000 is float.graph fix
                    !empty($res['rtp']) ? number_format($res['rtp'], 2, '.', '') : $rtp
                ];
            }
        }

        if (in_array($type, ['week', 'month'])) {
            $result = array_values($result);
        }

        $rtp_total = ($bets_total != 0) ? number_format($wins_total * 100 / $bets_total, 2, '.', '') : 0;

        $avg_time = phive('SQL')->sh($user_id)
            ->loadAssoc(sprintf("SELECT AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS avg FROM `users_game_sessions`
                      WHERE user_id = %d
                      AND start_time > '{$this->rtp_start_stamp}'
                      AND start_time BETWEEN '%s' AND '%s'
                      AND game_ref = '%s'", $user_id, $date1->format($hisfmt), $date2->format($hisfmt), $game_ref));

        $avg_time = ($avg_time && $avg_time['avg'] != NULL) ? $avg_time['avg'] : 0;
        $avg_time = ceil($avg_time);
        $avg_time = (($avg_time / 60) >= 60) ? gmdate("H:i:s", $avg_time) : gmdate("i:s", $avg_time);

        $avg_bets = phive('SQL')->sh($user_id)
            ->loadAssoc(sprintf("SELECT AVG(bet_cnt) AS avg FROM `users_game_sessions`
                      WHERE user_id = %d
                      AND start_time > '{$this->rtp_start_stamp}'
                      AND start_time BETWEEN '%s' AND '%s'
                      AND game_ref = '%s'", $user_id, $date1->format($hisfmt), $date2->format($hisfmt), $game_ref));
        $avg_bets = ($avg_bets && $avg_bets['avg'] != NULL) ? $avg_bets['avg'] : 0;
        $avg_bets = ceil($avg_bets);

        // Total amount of bets
        $sum_bets = phive('SQL')->sh($user_id)
            ->loadAssoc(sprintf("SELECT SUM(bet_cnt) AS bet_cnt_sum, SUM(bet_amount) AS bet_amount_sum  FROM `users_game_sessions`
                      WHERE user_id = %d
                      AND start_time > '{$this->rtp_start_stamp}'
                      AND start_time BETWEEN '%s' AND '%s'
                      AND game_ref = '%s'", $user_id, $date1->format($hisfmt), $date2->format($hisfmt), $game_ref));

        $sum_bets_cnt        =  ($sum_bets && $sum_bets['bet_cnt_sum'] != NULL) ? $sum_bets['bet_cnt_sum'] : 0;
        $sum_bets_ammount    = ($sum_bets && $sum_bets['bet_amount_sum'] != NULL) ? $sum_bets['bet_amount_sum'] : 0;
        $average_bet_ammount = $sum_bets_cnt > 0 ? phive()->twoDec($sum_bets_ammount / $sum_bets_cnt) : 0;
        $biggest_win         = $this->getBiggestWin($user_id, $game_ref, $date1->format($hisfmt), $date2->format($hisfmt));

        $result = [
            'data' => [
                'graph' => [
                    'data1' => $result,
                    'data2' => [],
                ],
                'rtp' => $rtp_total,
                'casino_rtp' => ($game['payout_percent'] * 100).'%',
                'feature_spins' => !empty($feature_spins) ? [
                        'remaining' => (is_numeric($feature_spins['remaining']) ? ($feature_spins['granted'] - $feature_spins['remaining']) : $feature_spins['remaining']),
                        'granted' => $feature_spins['granted']]
                    : ['remaining' => 0, 'granted' => 0],
                'hit_rate' => $this->getHitRate($user_id, $date1->format($hisfmt), $date2->format($hisfmt), $game_ref),
                'avg_time' => $avg_time,
                'avg_bets' => $avg_bets,
                'biggest_win' => $biggest_win[0],
                'biggest_win_bet' => $biggest_win[1],
                'total_spins' => $sum_bets,
                'total_spins' => $sum_bets_cnt,
                'total_bet' => $sum_bets_ammount,
                'average_bet_ammount' => $average_bet_ammount . ' ' . $currency,
                'debug' => $debug
            ],
            'type' => $type
        ];

        return $result;
    }

    function rtpGetSessionGraph($user, $session_id){
        $ud         = ud($user);
        $user_id    = $ud['id'];
        $currency   = $ud['currency'];
        $session_id = (int)$session_id;

        $res = $this->db->sh($user_id)->loadAssoc("
            SELECT start_time, end_time, game_ref, bet_amount, win_amount
            FROM users_game_sessions
            WHERE id = '$session_id'
            AND `user_id` = $user_id");

        $end_time  = phive()->isEmpty($res['end_time']) ? phive()->hisNow() : $res['end_time'];
        $result    = $result2 = [];
        $rtp_total = $hit_rate = $avg_bets = $avg_time = $wins_total = $bets_total = $sum_bets = $biggest_win = 0;

        if (!empty($res)) {

            $avg_time = strtotime($end_time) - strtotime($res['start_time']);
            $avg_time = (($avg_time / 60) >= 60) ? gmdate("H:i:s", $avg_time) : gmdate("i:s", $avg_time);

            $bet_sql = sprintf("SELECT amount, created_at, currency FROM bets
                                WHERE game_ref = '%s' AND `user_id` = '%s' AND created_at BETWEEN '%s' AND '%s' ORDER BY created_at ASC",
                                $res['game_ref'], $user_id, $res['start_time'], $end_time);

            $bets = $this->db->sh($user_id)->loadArray($bet_sql);
            $this->db->prependFromNodeArchive($bets, $user_id, $res['start_time'], $bet_sql, 'bets');

            foreach ($bets as $row) {
                $ts = strtotime($row['created_at']);
                //$date = date("Y-m-d", $ts);

                $result[] = [
                    $ts * 1000, // 1000 is float.graph fix
                    phive()->twoDec($row['amount'])
                ];

                $bets_total += $row['amount'];

                $avg_bets++;
            }

            $win_sql = sprintf("SELECT amount, created_at, currency FROM wins
                                WHERE game_ref = '%s' AND `user_id` = '%s' AND created_at BETWEEN '%s' AND '%s' ORDER BY created_at ASC",
                               $res['game_ref'], $user_id, $res['start_time'], $end_time);

            $wins = $this->db->sh($user_id)->loadArray($win_sql);
            $this->db->prependFromNodeArchive($wins, $user_id, $res['start_time'], $win_sql, 'wins');

            foreach ($wins as $row) {
                $ts = strtotime($row['created_at']);
                //$date = date("Y-m-d", $ts);

                $result2[] = [
                    $ts * 1000, // 1000 is float.graph fix
                    phive()->twoDec($row['amount'])
                ];

                $wins_total += $row['amount'];
            }

            if ($bets_total != 0) {
                $rtp_total = phive()->twoDec($wins_total * 100, $bets_total);
            }
            else {
                $rtp_total = 'N/A';
            }

            $hit_rate = $this->getHitRate($user_id, $res['start_time'], $end_time, $res['game_ref'], $session_id);

            $sum_bets = phive('SQL')->sh($user_id)
                ->loadAssoc(sprintf("SELECT SUM(bet_cnt) AS bet_cnt_sum, SUM(bet_amount) AS bet_amount_sum FROM `users_game_sessions`
                      WHERE user_id = %d
                      AND start_time > '{$this->rtp_start_stamp}'
                      AND start_time BETWEEN '%s' AND '%s'
                      AND game_ref = '%s'", $user_id, $res['start_time'], $end_time, $res['game_ref']));

            $sum_bets_cnt        = ($sum_bets && $sum_bets['bet_cnt_sum'] != NULL) ? $sum_bets['bet_cnt_sum'] : 0;
            $sum_bets_ammount    = ($sum_bets && $sum_bets['bet_amount_sum'] != NULL) ? $sum_bets['bet_amount_sum'] : 0;
            $average_bet_ammount = $sum_bets_cnt > 0 ? phive()->twoDec($sum_bets_ammount / $sum_bets_cnt) : 0;

            // get biggest win
            $biggest_win = $this->getBiggestWin($user_id, $res['game_ref'], $res['start_time'], $end_time);

            $game = phive('MicroGames')->getByGameRef($res['game_ref']);

            if ($game && in_array($game['network'], ['netent', 'playngo', 'thunderkick'])) {
                $feature_spins = phive('SQL')->sh($user_id, '', 'bonus_entries')
                    ->loadAssoc(sprintf("SELECT be.frb_remaining AS remaining, be.frb_granted AS granted FROM bonus_entries AS be, bonus_types AS bt WHERE
                            be.user_id = %d AND
                            be.start_time >= '%s' AND
                            be.end_time <= '%s' AND
                            be.bonus_type = 'freespin' AND
                            be.status='active' AND
                            be.bonus_id = bt.id AND
                            bt.game_id = '%s'", $user_id, substr($res['start_time'], 0, 10), substr($end_time, 0, 10), $res['game_ref']));
            }
            else {
                $feature_spins = ['remaining' => 'N', 'granted' => 'A'];
            }
        }

        $game = $this->getByGameRef($res['game_ref']);

        $result = [
            'data' => [
                'graph' => [
                    'data1' => $result,
                    'data2' => $result2,
                ],
                'rtp' => $rtp_total,
                'casino_rtp' => ($game['payout_percent'] * 100).'%',
                'feature_spins' => !empty($feature_spins[0])
                ?
                [
                    'remaining' => (is_numeric($feature_spins[0]['remaining']) ? ($feature_spins[0]['granted'] - $feature_spins[0]['remaining']) : $feature_spins[0]['remaining']),
                    'granted' => $feature_spins[0]['granted']
                ]
                :
                ['remaining' => 0, 'granted' => 0],
                'hit_rate' => $hit_rate,
                'avg_time' => $avg_time,
                'avg_bets' => $avg_bets,
                'biggest_win' => $biggest_win[0],
                'biggest_win_bet' => $biggest_win[1],
                'total_spins' => $sum_bets_cnt,
                'total_bet' => $sum_bets_ammount,
                'average_bet_ammount' => $average_bet_ammount . ' ' . $currency,
                'legends' => [
                    'bets' => t('bets'),
                    'wins' => t('wins'),
                ]
            ],
            'currency' => $currency,
            'type' => 'bets_wins'
        ];

        return $result;
    }

    function rtpGetGameSessions($user, $game_id, $dates=false, $sort='DESC', $limit=[0,10])
    {
        $user_id     = ud($user)['id'];
        $full_query  = sprintf("SELECT
                                   gs.id AS session_id, gs.start_time, gs.end_time, gs.game_ref, gs.win_amount, gs.bet_amount,
                                   ROUND((gs.win_amount * 100 / gs.bet_amount), 2) AS rtp,
                                   mg.id, mg.game_name, mg.game_url, mg.game_id
                               FROM
                                   users_game_sessions AS gs,
                                   micro_games AS mg
                               WHERE
                                    mg.ext_game_name = gs.game_ref
                                    AND (gs.bet_amount > 0 OR gs.win_amount > 0)
                                    AND mg.id = %d
                                    AND gs.user_id = %d
                                    AND gs.start_time BETWEEN '%s' AND '%s'
                                    AND gs.start_time > '{$this->rtp_start_stamp}'
                                    ORDER BY gs.start_time %s LIMIT %s",
                    (int) $game_id, $user_id, $dates[0], $dates[1], $sort, implode(',', $limit));

        //echo $full_query;

        $result = phive('SQL')->sh($user_id)->loadArray($full_query);
        return $result;
    }

    function rtpGetBetsWins($user, $session_id, $limit = [0,10])
    {
        $user_id    = uid($user);
        $session_id = (int)$session_id;
        $session    = phive('SQL')->sh($user_id)->loadAssoc("SELECT start_time, end_time, game_ref FROM users_game_sessions WHERE id = $session_id AND `user_id` = $user_id");

        if (empty($session)) {
            return [];
        }

        $mvcur_stamp = $user->getSetting('mvcur_stamp');
        // User has moved currency and the move happened after the session completed.
        if(!empty($mvcur_stamp) && $mvcur_stamp > $session['end_time']){
            $old_ids = cu($user_id)->getPreviousCurrencyUserIds();
            foreach ($old_ids as $old_id) {
                $old_user = cu($old_id);
                $mvcur_stamp = $old_user->getSetting('mvcur_stamp');

                // We take the old user id and assign it to user_id in order to fetch bets and wins from the old user instead.
                $user_id = $old_id;

                if ($mvcur_stamp <= $session['end_time']) {
                    break;
                }
            }
        }

        $end_time = phive()->isEmpty($session['end_time']) ? phive()->hisNow() : $session['end_time'];

        $base_sql   = "SELECT amount, '%s' AS type, created_at FROM %ss WHERE user_id = $user_id AND game_ref = '{$session['game_ref']}' AND created_at >= '{$session['start_time']}' AND created_at <= '$end_time'";
        $res = [];
        foreach(['bet', 'win'] as $type){
            $sql = sprintf($base_sql, $type, $type);
            $res[$type] = $this->db->sh($user_id)->loadArray($sql);
            $this->db->prependFromNodeArchive($res[$type], $user_id, $session['start_time'], $sql, $type . 's');
        }
        $result     = phive()->sort2d(array_merge($res['bet'], $res['win']), 'created_at');
        return $result;
    }

    function rtpGetListAll($user, $game = '', $dates = false, $sort = 'DESC', $limit = [0,10])
    {
        $user_id    = uid($user);
        $db         = phive('SQL');

        // Basic result
        $game_query = '';
        if (trim($game) != '' && mb_strlen(trim($game)) >= 3) {
            $game_query = " AND mg.game_name LIKE {$db->escape("%$game%")}";
        }

        $qry = '';
        if ($dates !== false) {
            $qry = sprintf(" AND gs.start_time BETWEEN '%s' AND '%s'", $dates[0], $dates[1]);
        }

        $sql = "
            SELECT
                gs.start_time, gs.end_time, gs.game_ref, mg.id, mg.game_name, mg.game_id, mg.game_url, (mg.payout_percent * 100) AS payout_percent
            FROM
                users_game_sessions AS gs
                INNER JOIN micro_games AS mg ON mg.ext_game_name = gs.game_ref AND mg.device_type_num = gs.device_type_num
            WHERE
                mg.tag != 'system'
                AND gs.start_time > '{$this->rtp_start_stamp}'
                AND gs.user_id = $user_id
                AND (gs.bet_amount > 0 OR gs.win_amount > 0)
                $game_query
                $qry
            ORDER BY
                gs.start_time $sort LIMIT ".implode(',', $limit);

        $result        = $db->sh($user_id)->loadArray($sql);

        if(empty($result)){
            return [];
        }

        $cur_month     = [date("Y-m-01"), phive()->hisMod('+1 day', null, 'Y-m-d')];
        $prev_month    = [date("Y-m-d", strtotime("first day of previous month")), date("Y-m-d", strtotime("last day of previous month"))];

        $grefs         = array_column($result, 'game_ref');
        $str_grefs     = $db->makeIn($grefs);


        // Current month
        $where_between = "AND start_time BETWEEN '{$cur_month[0]}' AND '{$cur_month[1]}'";

        $sessions_rtp_query = "SELECT (SUM(win_amount) * 100 / SUM(bet_amount)) AS rtp, game_ref FROM users_game_sessions
                            WHERE user_id = $user_id
                            AND start_time > '{$this->rtp_start_stamp}'
                            AND game_ref IN($str_grefs)
                            $where_between
                            GROUP BY game_ref";

        $cur_month_stats = $db->sh($user_id)->loadArray($sessions_rtp_query, 'ASSOC', 'game_ref');

        foreach ($result as &$row) {
            $row['rtp_month'] = $cur_month_stats[$row['game_ref']]['rtp'];
        }


        // Previous month
        $daily_rtp_query = "SELECT (SUM(wins) * 100 / SUM(bets)) AS rtp, game_ref FROM users_daily_game_stats
                            WHERE user_id = $user_id
                            AND game_ref IN($str_grefs)
                            AND date BETWEEN '{$prev_month[0]}' AND '{$prev_month[1]}'
                            GROUP BY game_ref";

        $prev_month_stats = $db->sh($user_id)->loadArray($daily_rtp_query, 'ASSOC', 'game_ref');

        foreach ($result as &$row) {
            $row['rtp_month_prev'] = $prev_month_stats[$row['game_ref']]['rtp'];
        }


        // All time
        $all_time_rtp_query = "SELECT (SUM(win_amount) * 100 / SUM(bet_amount)) AS rtp, game_ref FROM users_game_sessions
                               WHERE user_id = $user_id
                               AND start_time > '{$this->rtp_start_stamp}'
                               AND game_ref IN($str_grefs)
                               GROUP BY game_ref";

        $all_time_stats = $db->sh($user_id)->loadArray($all_time_rtp_query, 'ASSOC', 'game_ref');

        foreach ($result as &$row) {
            $row['rtp'] =  $all_time_stats[$row['game_ref']]['rtp'];
        }

        return $result;
    }

    function rtpGetListByUser($user, $game = '', $dates = false, $sort = 'DESC', $limit = [0,10], $fetchAll = false)
    {

        if(!in_array(strtolower($sort), ['desc', 'asc']))
            $sort = 'DESC';

        $order_by = $fetchAll ? 'gs.end_time '.$sort : 'rtp '.$sort;

        $user_id = ud($user)['id'];

        $game_query = '';
        if (trim($game) != '' && mb_strlen(trim($game)) >= 3) {
            $game_query = 'AND mg.game_name LIKE "%'.addslashes(trim($game)).'%"';
        }

        $qry = '';
        if ($dates !== false) {
            $qry = sprintf(" AND gs.start_time BETWEEN '%s' AND '%s'", $dates[0], $dates[1]);
        }

        $sql_str = "SELECT
                        gs.start_time, gs.end_time, gs.game_ref,
                        (SUM(gs.win_amount) * 100 / SUM(gs.bet_amount)) AS rtp,
                        mg.id, mg.game_name, mg.game_url, mg.game_id
                    FROM users_game_sessions AS gs
                    LEFT JOIN micro_games AS mg ON mg.ext_game_name = gs.game_ref
                    WHERE mg.tag != 'system'
                        $game_query
                        AND gs.start_time > '{$this->rtp_start_stamp}'
                        AND gs.bet_amount > 0
                        AND gs.win_amount > 0
                        AND gs.user_id = $user_id $qry GROUP BY gs.game_ref ORDER BY $order_by LIMIT ".implode(',', $limit);

        return phive('SQL')->sh($user_id)->loadArray($sql_str);
    }

    function formatRtpResult(array $result, DBUser $user): array
    {
        $output = [];

        foreach ($result as $row) {

            if (!empty($row['start_time'])) {
                $d = new DateTime($row['start_time']);
                $row['start_time_dt'] = phive()->lcDate($d->getTimestamp(), '%x');
                $row['start_time']    = $d->format('H:i:s');
            }
            if (!empty($row['created_at'])) {
                $d = new DateTime($row['created_at']);
                $row['created_at']      = phive()->lcDate($d->getTimestamp(), '%x');
                $row['created_at_time'] = $d->format('H:i:s');
            }

            $row['rtp_prc'] = formatRTP($row['rtp']);
            $row['rtp_prc_month'] = formatRTP($row['rtp_month']);
            $row['rtp_prc_month_prev'] = formatRTP($row['rtp_month_prev']);

            $row['payout_prc']         = round($row['payout_percent'], 2);
            //$row['img_url']            = fupUri('thumbs/'.$row['game_id'].'_c.jpg', true);
            $row['img_url']            = $this->carouselPic($row);

            if(empty($row['bet_amount']) && !empty($row['win_amount'])){
                // We have session with wins but no bets (yes it is possible)
                $row['rtp_prc'] = 'N / A';
                $row['rtp']     = 'N / A';
            }

            if (!empty($row['win_amount'])) {
                $row['win_amount'] = phive()->twoDec($row['win_amount'] ) . ' ' . $user->getCurrency();
            }

            if (!empty($row['bet_amount'])) {
                $row['bet_amount'] = phive()->twoDec($row['bet_amount']) . ' ' . $user->getCurrency();
            }

            if (!empty($row['amount'])) {
                $row['amount'] = phive()->twoDec($row['amount']);
            }

            $output[] = $row;
        }

        return $output;
    }

    public function rtpGetByUserAPI($user): array
    {
        $user_id = ud($user)['id'];
        $rtp = [];
        $result = $this->rtpGetListByUser($user, '', false, 'ASC', [1]);

        $rtp['low'] = [
            'rtp' => formatRTP($result[0]['rtp']) . '%',
            'title' => 'account.mobile-rtp_low.headline'
        ];

        $result = $this->rtpGetListByUser($user, '', false, 'DESC', [1]);

        $rtp['hi'] = [
            'rtp' => formatRTP($result[0]['rtp']) . '%',
            'title' => 'account.mobile-rtp_hi.headline'
        ];

        $result = phive('SQL')->sh($user_id, '', 'users_lifetime_stats')
            ->loadArray("SELECT SUM(wins) AS win, SUM(bets) AS bet, (wins * 100 / bets) AS `rtp` FROM users_lifetime_stats WHERE user_id = '".$user_id."'");

        $rtp['overall'] = [
            'rtp' => formatRTP($result[0]['rtp']) . '%',
            'title' => 'account.overall-mobile-rtp.headline',
        ];

        $rtp['search_form'] = [
            'date_from' => date('d-m-Y', strtotime('-7 days')),
            'date_to' => date('d-m-Y'),
            'time_from' => '00:00',
            'time_to' => '23:59',
        ];

        return $rtp;
    }
    public function rtpGetByUser($user)
    {
        $user_id = ud($user)['id'];
        //$user_id = 5129328;

        $rtp = [];

        $result = $this->rtpGetListByUser($user, '', false, 'ASC', [1]);

        //echo '<pre>'.print_r($result, true).'</pre>';

        $rtp['low'] = [
            'rtp' => formatRTP($result[0]['rtp']),
            'game' => $result[0]['game_name'],
            'game_url' => $result[0]['game_url'],
            'game_id' => $result[0]['game_id']
        ];

        $result = $this->rtpGetListByUser($user, '', false, 'DESC', [1]);

        //echo '<pre>'.print_r($result, true).'</pre>';

        $rtp['hi'] = [
            'rtp' => formatRTP($result[0]['rtp']),
            'game' => $result[0]['game_ref'],
            'game_url' => $result[0]['game_url'],
            'game_id' => $result[0]['game_id']
        ];

        // change bets to bets_cnt? Add table field?
        $result = phive('SQL')->sh($user_id, '', 'users_lifetime_stats')
            ->loadArray("SELECT SUM(wins) AS win, SUM(bets) AS bet, (wins * 100 / bets) AS `rtp` FROM users_lifetime_stats WHERE user_id = '".$user_id."'");

        $rtp['overall'] = formatRTP($result[0]['rtp']);

        //echo '<pre>'.print_r($rtp, true).'</pre>';

        return $rtp;
    }

    function featuresList($game_id, $type='feature', $name='') { // types = feature/info
        $sql     = phive('SQL');
        $game_id = (int)$game_id;
        $qry     = '';

        if ($name != '') {
            $name = $sql->escape($name,false);
            $qry = 'AND name = "'.$name.'"';
        }

        $result = $sql->loadArray(sprintf('SELECT * FROM game_features WHERE game_id = %d AND type = "%s" %s', $game_id, $type, $qry), 'ASSOC', 'name');

        return $result;
    }

    function parseJps($network = '')
    {
        $executionId = uniqid();
        $logger = phive('Logger')->getLogger('cron');
        $logContext = [
            'execution_id' => $executionId,
            'started_at' => phive()->hisNow(),
            'brand' => phive('BrandedConfig')->getBrand(),
        ];
        $logger->info('parseJackpots: started', $logContext);

        $lockKey = 'parseJps_lock_' . phive('BrandedConfig')->getBrand();
        $lockTimeout = 1800; // or 30 minutes
        $existingLock = phMget($lockKey);
        // If the lock already exists, exit the function
        if (!is_null($existingLock)) {
            $logger->warning("parseJackpots: skipped", $logContext);
            return;
        }
        phMset($lockKey, 1, $lockTimeout);

        try {
            $sql = phive('SQL');

            if (empty($network)) {
                $networks = $this->getSetting('jp_networks');
                // Get all the jackpot available for the providers in the jp_networks setting
                foreach ($networks as $network) {
                    $module = phive('Casino')->getLegacyNetworkName($network);
                    // if module does not exists or function does not exist it will skip the parsing of jackpots
                    if (empty($module) || !method_exists($module, 'parseJackpots')) {
                        continue;
                    }

                    $logger->info('parseJackpots: step0', [
                        'execution_id' => $executionId,
                        'game_provider' => strtolower($module),
                    ]);

                    $startTime = microtime(true);
                    $parseJackpots = phive($module)->parseJackpots();
                    $requestEndTime = microtime(true) - $startTime;

                    $startTime = microtime(true);
                    $res = $sql->insertTable('micro_jps', $parseJackpots);
                    $databaseEndTime = microtime(true) - $startTime;

                    $logContext[strtolower($module)]  = [
                        "storing_jackpots_count_result" => $res,
                        "storing_jackpots_count_items" => count($parseJackpots),
                        "storing_jackpots_time" => number_format($databaseEndTime, 2, '.', '') . ' seconds',
                        "fetching_jackpots_time" => number_format($requestEndTime, 2, '.', '') . ' seconds',
                    ];
                }

                $logger->info('parseJackpots: step1', [
                    'execution_id' => $executionId,
                    'ended_at' => phive()->hisNow(),
                ]);

                // get all JPs
                $startTime = microtime(true);
                $query_all = "SELECT * FROM micro_jps WHERE tmp = '1'";
                $all_jps = $sql->loadArray($query_all);
                $all_array = [];
                foreach ($all_jps as $jp_index => $jp) {
                    // the array is equal to the array index in order to be retrieved later when needed
                    $all_array[$jp['jurisdiction']][$jp['jp_id'].'-'.$jp['module_id']][$jp['currency']] = $jp_index;
                }

                $databaseEndTime = microtime(true) - $startTime;
                $logContext['retrieving_all_jackpots_time'] = number_format($databaseEndTime, 2, '.', '') . ' seconds';
                $logger->info('parseJackpots: step2', [
                    'execution_id' => $executionId,
                    'ended_at' => phive()->hisNow(),
                ]);

                $startTime = microtime(true);
                $insert_array = [];
                $base_currency = phive('Currencer')->baseCur();
                $allCurrencies = phive('Currencer')->getAllCurrencies();

                foreach ($all_array as $game => $game_id) {
                    foreach ($game_id as $g => $currency) {

                        // Loop through all the currency
                        foreach($allCurrencies as $ciso => $c){
                            // If the currency is not in the array then need to convert it and insert it
                            // Also must check that the EUR value is there
                            if(count($currency[$ciso]) == 0 && count($currency[$base_currency]) > 0 && $ciso != $base_currency) {
                                $insert_array[] = $this->convertFxJps($all_jps[$currency[$base_currency]],$base_currency,$ciso);
                            }
                        }
                    }
                }

                $databaseEndTime = microtime(true) - $startTime;
                $logContext['adding_missing_jackpots_time'] = number_format($databaseEndTime, 2, '.', '') . ' seconds';

                $startTime = microtime(true);
                $sql->insertTable('micro_jps', $insert_array);
                // delete all the jackpots that have tmp 0
                $sql->query("DELETE FROM micro_jps WHERE tmp = 0");
                // update all the jackpots to tmp 0
                // this must be done to all rows since if there is no conversion we still need to change the tmp to 0
                $sql->query("UPDATE micro_jps SET tmp = 0");

                $this->jpLogCron();
                $databaseEndTime = microtime(true) - $startTime;
                $logContext['updating_tmp_entries_time'] = number_format($databaseEndTime, 2, '.', '') . ' seconds';

                $logger->info('parseJackpots: step3', [
                    'execution_id' => $executionId,
                    'ended_at' => phive()->hisNow(),
                ]);
            } else {
                // to be run every 5 mins
                $sql->query("DELETE FROM micro_jps WHERE network = '$network'");
                $module = phive('Casino')->getLegacyNetworkName($network);
                $parseJackpots = phive($module)->parseJackpots();
                $sql->insertTable('micro_jps', $parseJackpots);
                $this->jpLogCron($network);
            }
        } catch (Exception $e) {
            $logContext['error'] = $e->getMessage();
        } finally {
            $logContext['ended_at'] = phive()->hisNow();
            $logContext['memory_limit'] = ini_get('memory_limit');
            $logContext['used_memory_peak'] = ((memory_get_peak_usage() / 1024) / 1024) . 'M';
            $logger->info('parseJackpots: finished', $logContext);

            phMdel($lockKey);
        }
    }



    function convertFxJps($jp, $base_cur = 'EUR', $change_to_curr, $change = true){

        $cur = phive('Currencer');
        $jp['id'] = '';
        $jp['jp_value'] = $change ? $cur->changeMoney($base_cur, $change_to_curr, $jp['jp_value']) : $jp['jp_value'];
        $jp['currency'] = $change_to_curr;
        return $jp;
    }


    function getPriorJpRow($jp_id, $currency, $jurisdiction){
        return phive('SQL')->loadAssoc("SELECT * FROM jp_log WHERE jp_id = '$jp_id' AND currency = '$currency' AND jurisdiction = '$jurisdiction' ORDER BY id DESC LIMIT 0,1");
    }

    /*
       CREATE TABLE IF NOT EXISTS `jp_log` (
       `id` bigint(21) NOT NULL AUTO_INCREMENT,
       `jp_value` bigint(21) NOT NULL,
       `jp_id` varchar(200) NOT NULL,
       `jp_name` varchar(255) NOT NULL,
       `jurisdiction` varchar(10) NOT NULL,
       `network` varchar(25) NOT NULL DEFAULT 'microgaming',
       `currency` varchar(5) NOT NULL,
       `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       `game_ref` varchar(55) NOT NULL,
       `contributions` bigint(21) NOT NULL,
       `trigger_amount` bigint(21) NOT NULL,
       `configuration` varchar(55) NOT NULL,
       PRIMARY KEY (`id`),
       UNIQUE KEY `jp_id` (`jp_id`,`created_at`,`currency`,`jurisdiction`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    TODO, Function below is a temporal workaround to get the log working, needs to be completely refactored /Ricardo
     */
    function insertJpLog($jp){
        $created_at    = phive()->hisNow();
        $where = "jp_id = '{$jp['jp_id']}' AND currency = '{$jp['currency']}' AND created_at = '$created_at' AND jurisdiction = '{$jp['jurisdiction']}'";
        $exists = phive("SQL")->getValue("SELECT id FROM jp_log WHERE {$where}");

        if($exists){
            return;
        }

        $pr            = $this->getPriorJpRow($jp['jp_id'], $jp['currency'], $jp['jurisdiction']);
        $diff          = $jp['jp_value'] - $pr['jp_value'];
        if($diff > 0){
            $contributions   = $diff;
            $trigger_amount  = 0;
        }else{
            //We have a trigger, note that contributions here is not 100% correct as contribs might have happened, we need base reset amount for each jp to fix this.
            $contributions   = 0;
            $trigger_amount  = abs($diff);
        }

        if ($jp['network'] == 'microgaming') {
            $game = phive("SQL")->loadArray("SELECT * FROM micro_games WHERE module_id = '{$jp['module_id']}'");
            $ratio = $game[0]['jackpot_contrib'] ?? 0; //Super ugly solution
        } else {
            $ratio = $this->getByGameRef($jp['module_id'])['jackpot_contrib'];
        }

        if (empty($ratio)) {
            return; // Game does not exist, temporal workaround
        }

        $insert = [
            'jp_value'        => $jp['jp_value'],
            'jp_id'           => $jp['jp_id'],
            'jurisdiction'    => $jp['jurisdiction'],
            'jp_name'         => $jp['jp_name'],
            'network'         => $jp['network'],
            'currency'        => $jp['currency'],
            'game_ref'        => $jp['module_id'],
            'contributions'   => $contributions,
            'configuration'   => "Contribution ratio: {$ratio}",
            'trigger_amount'  => $trigger_amount,
            'created_at'      => $created_at
        ];

        phive('SQL')->insertArray('jp_log', $insert);
    }

    function jpLogCron($network = ''){
        if (empty($this->getSetting('jp_log_cron', true))) {
            return;
        }

        $and_where  = empty($network) ? '' :  " AND network = '$network'";
        $jps        = phive("SQL")->loadArray("SELECT * FROM micro_jps WHERE 1{$and_where}");

        foreach($jps as $jp)
            $this->insertJpLog($jp);
    }

    /**
     * Close ugs records at least X seconds old
     * @param int $age Age in seconds of the sessions we want to close. Default: 9h
     * @return void
     */
    public function timeoutGameSessions(int $age = 32400, $logId = "na")
    {
        try {
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutGameSessions start " . $logId);
            $start_time = phive()->hisNow("-{$age} seconds");
            $zero_time = phive()->getZeroDate();

            // We get all open sessions older than our delta
            $open = phive('SQL')->shs('merge', '', null, 'users_game_sessions')
                ->loadArray("SELECT * FROM users_game_sessions WHERE end_time = '{$zero_time}' AND start_time <='{$start_time}'");

            if ($open) {
                $casino = phive('Casino');
                foreach ($open as $ugs) {
                    $casino->finishUniqueGameSession($ugs);
                }
            }
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutGameSessions end " . $logId);
        } catch (\Throwable $e) {
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutGameSessions "  . $logId, [$e]);
        }
    }

  function makeShowOps($show_ops, $prefix = '', $not = ''){
      if(!empty($show_ops)){
          $show_ops_str = phive('SQL')->makeIn($show_ops);
          $where_ops = "AND {$prefix}operator {$not} IN($show_ops_str)";
      }
      return $where_ops;
  }

    private function array_value_recursive($key, array $arr): array {
        $val = [];
        array_walk_recursive($arr, function ($v, $k) use ($key, &$val) {
            if ($k == $key) array_push($val, $v);
        });

        return $val;
    }


    public function gameOperators(array $games, int $active = 1, string $device_type = '', $hide_ops = array(), $show_ops = array(), $gps_via_gpr = false): array {
        $gameIds = $this->array_value_recursive('id', $games);
        $operatorsArr = [];

        if (count($gameIds)){
            $gameIdsStr = implode(',', $gameIds);

            if(!empty($hide_ops)){
                $where_not_ops = $this->makeShowOps($hide_ops,'','NOT');
            } else if (!empty($show_ops)) {
                $where_ops = $this->makeShowOps($show_ops);
            }
            $where_not_ops = $this->makeShowOps($hide_ops,'','NOT');
            $where_extra = ($active === 1 || $active === 0) ? "WHERE active = $active" : '';
            $where_device = empty($device_type) ? '' : " AND device_type = '$device_type'";
            $where_country = " AND blocked_countries NOT LIKE '%". cuCountry() . "%'";
            $where_province = $this->addWhereProvinceClousure(' AND ');
            $where_ids = " AND id IN($gameIdsStr)";
            if($gps_via_gpr) {
                $gps_list = implode("','", phive('Casino')->getSetting('gps_via_gpr'));
                $where_extra .= " AND network IN ('$gps_list' )";
            }
            $sql = "SELECT operator FROM micro_games $where_extra $where_device $where_country $where_province $where_not_ops $where_ops $where_ids AND operator != '' GROUP BY operator";

            $operatorsArr = phive("SQL")->lb()->load1DArr($sql, 'operator', 'operator');
        }

        return $operatorsArr;
    }


    function incPlayedTimes($gref, $device_type_num = 0){
    $device_type_num = intval($device_type_num);
    phive('SQL')->query("UPDATE micro_games SET played_times = played_times + 1 WHERE ext_game_name = '$gref' AND device_type_num = $device_type_num");
  }

    function countWhere($device = 'flash', $tag = '')
    {
        $where = ['active = 1'];
        if (!empty($device)) {
            $where[] = "device_type = '$device'";
        }
        if (!empty($tag)) {
            $where[] = "tag = '$tag'";
        }
        $country = phive('Licensed')->getLicCountry(cu());
        // We skip if country is empty to avoid breaking on localhost/test, cause the "IP check" will return an empty string.
        if(!empty($country)) {
            $where[] = "blocked_countries NOT LIKE '%$country%'";
        }
        $whereProvince = $this->addWhereProvinceClousure();
        if($whereProvince !== '') $where[] = $whereProvince;
        $sql = "SELECT COUNT(*) FROM micro_games WHERE " . implode(' AND ', $where);

        $res = phQget($sql);
        if(!empty($res)) {
            return $res;
        }

        $res = phive("SQL")->lb()->getValue($sql);
        phQset($sql, $res, 3600);

        return $res;
    }

  function incCache($gref, $date = '', $device_type_num = 0){
    if(!empty($_SESSION['played'][$gref.$device_type_num]))
      return false;
    $_SESSION['played'][$gref.$device_type_num] = true;
    $date    = empty($date) ? date('Y-m-d') : $date;
    $country = cuCountry();
    $cache   = phive("SQL")->loadAssoc('', 'game_cache',
     "game_ref = '$gref'
      AND day_date = '$date'
      AND device_type_num = $device_type_num
      AND country = '$country'");
    if(empty($cache)){
      phive("SQL")->insertArray('game_cache', array(
        'game_ref' => $gref,
        'day_date' => $date,
        'played_times' => 1,
        'device_type_num' => $device_type_num,
        'country' => $country));
    }else{
      $cache['played_times']++;
      phive("SQL")->save('game_cache', $cache);
    }
  }

  function getPopular($num = 10, $tag = '', $where_device = "mg.device_type = 'flash'", $should_join_jps = false) {
    $num = intval($num);
    $where = " WHERE $where_device ";
    $where .= empty($tag) ? "" : " AND tag = '$tag' ";

    [$sel_games_overrides, $join_games_overrides] = $this->getGamesOverridesJoin();

    $sel_jps = '';
    $join_jps = '';
    if ($should_join_jps) {
      [$sel_jps, $join_jps] = $this->getJoinJps();
    }

    return phive('SQL')->loadArray("
        SELECT mg.* $sel_games_overrides $sel_jps
        FROM {$this->table} AS mg
        $join_games_overrides
        $join_jps
        $where
        GROUP BY mg.id
        ORDER BY played_times DESC, override_payout_extra_percent DESC
        LIMIT 0,$num
    ");
  }

  function getPlayedOn($date = '', $num = 10, $where_device = "mg.device_type = 'flash'"){
    $date = empty($date) ? phive()->modDate('', '-1 day') : $date;
    $str = "SELECT DISTINCT mg.*
      FROM {$this->table} mg, game_cache gc
      WHERE $where_device AND mg.active = 1
      AND mg.ext_game_name = gc.game_ref
      AND gc.day_date = '$date'
      ORDER BY gc.played_times DESC LIMIT 0,$num";

      $res = phQget($str);
      if(!empty($res)) {
          return $res;
      }

      $res = phive("SQL")->doDb('replica')->loadArray($str);

      phQset($str, $res, rand(3600, 4600));
      return $res;
  }

  function getAllGameNames(){
      return phive('SQL')->lb()->load1DArr("SELECT * FROM ".$this->table(), 'game_name');
  }

  function getFromIdArr($arr){
    $str = phive('SQL')->makeIn($arr);
    return phive('SQL')->lb()->loadArray("SELECT * FROM {$this->table} WHERE game_id IN($str)");
  }

  function getFromExtGameNameArr($arr, $deviceType){
    $str = phive('SQL')->makeIn($arr);
    return phive('SQL')->lb()->loadArray("SELECT * FROM {$this->table} WHERE ext_game_name  IN($str) AND device_type_num = '$deviceType'");
  }

  function getRand($count, $where_device = "device_type = 'flash'"){
    $count = intval($count);
    return phive('SQL')->lb()->loadArray("SELECT * FROM ".$this->table()." WHERE $where_device ORDER BY RAND() LIMIT 0,$count");
  }

    /**
     * Basic wrapper around micro_games table to get all games with basic filtering conditions.
     *
     * @param string $where_extra
     * @param string $fields
     * @param string $device_type
     * @param bool $block
     * @param bool $should_join_jps
     * @return array|mixed|string
     */
    public function getAllGames($where_extra = '', $fields = '*', $device_type = 'flash', $block = false, $should_join_jps = false)
    {
        $where = " WHERE mg.device_type = '{$device_type}' ";
        $where .= $this->blockCountry($block);
        if (!empty($where_extra)) {
            $where .= " AND $where_extra ";
        }

        [$mobile_extra_select, $mobile_extra_join] = $this->overrideGameUrlByDeviceType($device_type);

        //In case device type is mobile, and as we are doing left join with the table itself (alias mg_desktop) on mobile_id
        //As the columns are the same for mg and mg_desktop, the result set will include both, and there may be ambiguity or conflicts
        if ($device_type === 'html5' && $fields === '*') {
            $fields = 'mg.*';
        }

        $select_jps = '';
        $join_jps = '';
        if ($should_join_jps){
            [$select_jps, $join_jps] = $this->getJoinJps();
        }

        $str = "
            SELECT {$fields} {$mobile_extra_select} {$select_jps}
            FROM {$this->table()} mg
            {$mobile_extra_join} {$join_jps}
            {$where}
            GROUP BY mg.id";

        return phive('SQL')->lb()->loadArray($str);
    }

  public function getJoinJps(): array
  {
      $ciso = ciso();
      $sel_jps 	= ", micro_jps.id AS jp_id, micro_jps.jp_name, MAX(micro_jps.jp_value) as jp_value";
      $join_jps = "
        LEFT JOIN micro_jps ON
          (micro_jps.module_id = mg.module_id OR micro_jps.module_id = mg.ext_game_name)
          AND micro_jps.tmp = 0
          AND micro_jps.module_id != ''
          AND micro_jps.currency = '$ciso'
      ";

      return [$sel_jps, $join_jps];
  }

  function getGamesOrderLimit($order_by, $limit, $where_device = "device_type = 'flash'"){
    $limit = intval($limit);
    return phive('SQL')->lb()->loadArray("SELECT * FROM ".$this->table()." WHERE $where_device ORDER BY $order_by DESC LIMIT 0,$limit");
  }

  function allGamesSelect($col = 'game_id', $where_extra = '', $where_device = "device_type = 'flash'", $display = 'game_name'){
    $where = " WHERE $where_device ";
    $where .= empty($where_extra) ? '' : " AND $where_extra ";
    $str = "SELECT * FROM {$this->table()} $where ORDER BY game_name";
    return phive('SQL')->readOnly()->lb()->loadKeyValues($str, $col, $display);
  }

  function selAllGamesShowDevice($col = 'game_id'){
    return $this->allGamesSelect($col, '', 1, array('game_name', 'device_type', 'network', 'active'));
  }

  function allJpsSelect($col = 'id'){
    return phive('SQL')->loadKeyValues("SELECT * FROM micro_jps", $col, 'jp_name');
  }

  /**
   * Get payout ratios.
   *
   * @param string $ym
   * @param string $asc_desc
   * @param string $device_type
   * @param string $alias
   * @param string $where_extra
   * @return array
   */
  public function getPayoutRatios(string $ym = '', string $asc_desc = 'bs DESC', string $device_type = 'flash', string $alias = '', string $where_extra = ''): array {
      $tag_id = $this->getTagIdFromAlias($alias);
      $res = $this->getByPaymentRatio($ym, $asc_desc, $device_type, $tag_id ?: $alias, $where_extra);
      $th = 1000;
      $games = array_filter($res, function($el) use ($th) {
          return $el['bs'] >= $th;
      });
      $mgGamePayoutBox = GamesFilterService::initializePayoutBox();
      array_walk($games, function(&$game) use ($mgGamePayoutBox) {
          $game['image'] = $game['mobile_bkg_pic'] ? fupUri("backgrounds/{$game['mobile_bkg_pic']}", true) : $this->carouselPic($game);
          $game['last_updated'] = phive()->lcDate("{$mgGamePayoutBox->cron_day} 0{$mgGamePayoutBox->cron_h}:00:00") . ' ' . t('cur.timezone');
          $game['arrow'] = $mgGamePayoutBox->getArrow($game);
          unset($game['mobile_bkg_pic']);
          unset($game['bkg_pic']);
      });
      return $games;
  }

  function getByPaymentRatio($ym = '', $asc_desc = 'bs DESC', $device_type = 'flash', $tag_id = '', $where_extra = ''): array
  {

      $ym = phive('SQL')->sanitizeDate($ym);

      $block_where = $this->blockCountry();

      $jurisdiction = $this->getCountryForOverride();

    $mod = 0;
    if(empty($ym)){
      $group_by = ' GROUP BY mg.ext_game_name ';
      $select = "SUM(DISTINCT gmc.win_sum) AS ws,
        SUM(gmc.bet_sum) AS bs,
        ((SUM(gmc.win_sum) + $mod) / (SUM(gmc.bet_sum) + $mod)) AS payout_ratio,
        ((SUM(gmc.prior_win_sum) + $mod) / (SUM(gmc.prior_bet_sum) + $mod)) AS prior_payout_ratio,";
    }else{
      $where_ym = " AND gmc.day_date = '$ym' ";
      $select = "DISTINCT gmc.win_sum AS ws,
        gmc.bet_sum AS bs,
        ((gmc.win_sum + $mod) / (gmc.bet_sum + $mod)) AS payout_ratio,
        ((gmc.prior_win_sum + $mod) / (gmc.prior_bet_sum) + $mod) AS prior_payout_ratio,";
    }

    if(empty($asc_desc)) {
      $asc_desc = 'bs DESC';
    }

    $join_where_tag_id = '';
    if(!empty($tag_id)) {
        $join_where_tag_id = " AND mg.id IN(SELECT game_id FROM game_tag_con WHERE tag_id = $tag_id) ";
    }


      $join_where_mobile_id = '';
      $select_mobile_bkg_pic = '';
      if(phive()->isMobile()) {
          $join_where_mobile_id .= "LEFT JOIN micro_games AS mg_mobile
        ON mg.mobile_id = mg_mobile.id";
          $select_mobile_bkg_pic = ',mg_mobile.bkg_pic as mobile_bkg_pic';
      }
    $str = "SELECT
        $select
        mg.id,
        mg.game_id,
        mg.game_name,
        mg.bkg_pic,
        COALESCE(mgo.payout_percent, mg.payout_percent) AS rtp
        $select_mobile_bkg_pic
      FROM game_month_cache gmc
      LEFT JOIN micro_games AS mg
        ON mg.ext_game_name = gmc.game_ref
        AND mg.device_type = '$device_type'
        AND mg.active = 1
        $join_where_tag_id
      LEFT JOIN game_country_overrides AS mgo ON mgo.game_id = mg.id AND mgo.country IN ('$jurisdiction', 'ALL')
      $join_where_mobile_id
      WHERE 1
      $where_ym
      $where_extra
      $block_where
      $group_by";

    // having removed ORDER BY from the query we need to append $asc_desc to keep the uniqueness of the redis key.
    $redisKey = $str.$asc_desc;

    $res = phQget($redisKey);
    if(!empty($res)) {
      return $res;
    }

    $res = phive("SQL")->doDb('replica')->loadArray($str);

    list($order_column, $order_sort) = explode(' ',strtolower($asc_desc));
    $res = phive()->sort2d($res, $order_column, $order_sort);

    phQset($redisKey, $res, rand(36000, 46000));
    return $res;
  }

  function getBetWinStats($sdate, $edate){
    $str = "SELECT ms.game_id, ms.ext_game_name, us.*, SUM(us.bets) AS bet_sum, SUM(us.wins) AS win_sum FROM users_daily_game_stats us
        LEFT JOIN micro_games AS ms ON ms.ext_game_name = us.game_ref
        WHERE us.date >= '$sdate'
        AND us.date <= '$edate'
            GROUP BY us.game_ref";

      //return phive("SQL")->shs(false, '', null, 'users_daily_game_stats')->loadArray($str, 'ASSOC', 'game_ref');
      return phive("SQL")->loadArray($str, 'ASSOC', 'game_ref');
  }

  function calcMonthGameStats($ym, $sdate, $edate, $redo = false){
    $old 	= phive("SQL")->loadArray("SELECT * FROM game_month_cache WHERE day_date = '$ym'", 'ASSOC', 'game_ref');
    phive("SQL")->query("DELETE FROM game_month_cache WHERE day_date = '$ym'");
    $gcache = phive("SQL")->loadArray("
      SELECT *, SUM(played_times) as played_month FROM game_cache
      WHERE day_date >= '$sdate'
      AND day_date <= '$edate'
      GROUP BY game_ref, device_type_num, country");
    $insert = array();
    $stats 	= $this->getBetWinStats($sdate, $edate);


    if($redo){
      $edate 			= phive()->modDate($edate, '-5 day');
      $redo_stats 	= $this->getBetWinStats($sdate, $edate);
    }

    foreach($gcache as $g){
      $insert[] = array(
        'game_ref' 			=> $g['game_ref'],
        'country' 			=> $g['country'],
        'device_type_num'	=> $g['device_type_num'],
        'day_date' 			=> $ym,
        'played_times' 		=> $g['played_month'],
        'bet_sum' 			=> $stats[$g['game_ref']]['bet_sum'],
        'win_sum' 			=> $stats[$g['game_ref']]['win_sum'],
        'prior_bet_sum' 	=> $redo ? $redo_stats[$g['game_ref']]['bet_sum'] : $old[$g['game_ref']]['bet_sum'],
        'prior_win_sum' 	=> $redo ? $redo_stats[$g['game_ref']]['win_sum'] : $old[$g['game_ref']]['win_sum']);
    }

    phive("SQL")->insert2DArr('game_month_cache', $insert);
  }

  function getGameTags($game) {
      $sql = "SELECT * FROM game_tag_con WHERE game_id = {$game['id']}";
      return phive('SQL')->loadArray($sql);
  }

    /**
     * check if given game is a Live Casino game or not
     * @param $game
     * @return boolean
     */
    public function isLiveCasinoGame($game) {
        if ($game['tag'] === 'live-casino') {
            return true;
        }

        $game_tags = phive('MicroGames')->getGameTags($game);
        $filtered = array_filter($game_tags, function ($tag) {
            return in_array($tag['tag_id'], phive('Licensed/IT/IT')->getLicSetting('hide_popup_for_live_casino'));
        });

        return count($filtered) > 0;
    }

  function getTagIdFromAlias($alias){
    return phive("SQL")->getValue('', 'id', 'game_tags', "alias = '$alias'");
  }

    /**
     * Return a where condition (" AND xxx ") to filter games based on country
     *
     * @param bool $block - default "true" will add blocked/included_countries condition in the query, if "false" no filtering will be applied
     * @param string $tbl - table prefix in case we are not using default "mg." // TODO check all instances and uniform it so we can remove it
     * @return string
     */
    public function blockCountry($block = true, $tbl = 'mg.')
    {
        $str = '';
        if ($block) {
            $cur_country = cuCountry('', true);
            if (!empty($cur_country)) {
                $whereProvince = $this->addWhereProvinceClousure("AND {$tbl}");
                $str = " AND {$tbl}blocked_countries NOT LIKE '%$cur_country%' {$whereProvince} AND ({$tbl}included_countries = '' OR {$tbl}included_countries LIKE '%$cur_country%')";
            }
            if (!isLogged()) {
                $str .= " AND {$tbl}blocked_logged_out NOT LIKE '%$cur_country%'";
            }
        }
        return $str;
    }

    /**
     * If you need to use this function for an already existing scenario @see getTaggedByWrapper that probably will
     * take care of your needs and it's less complex to use.
     *
     * @param string $tag
     * @param mixed $offs
     * @param mixed $length
     * @param mixed $sub
     * @param string $order_by
     * @param string $where_device
     * @param string $play_count_period
     * @param mixed $should_join_jps
     * @param mixed $block
     * @param string $operator
     * @param array $hide_ops
     * @param array $show_ops
     * @param bool $caching In case we don't want to do caching
     * @return array
     */
    public function getTaggedBy(
        $tag = 'all',
        $offs = null,
        $length = null,
        $sub = null,
        $order_by = 'mg.game_name ASC',
        $where_device = "mg.device_type = 'flash'",
        $play_count_period = '',
        $should_join_jps = false,
        $block = false,
        $operator = '',
        $hide_ops = array(),
        $show_ops = array(),
        $caching = true,
        $extra_where = '',
        $gps_via_gpr = false,
        $user_id = null,
        $favorite = false
    ) {

    $where_ops = '';
    if (!empty($hide_ops)) {
        $where_ops = $this->makeShowOps($hide_ops, 'mg.', 'NOT');
    } elseif (!empty($show_ops)) {
        $where_ops = $this->makeShowOps($show_ops, 'mg.');
    }

    $where = " WHERE $where_device AND mg.active = 1 ";

    if($tag == 'all' || $tag == array('all'))
      $where .= '';
    else if(is_array($tag))
      $where .= !empty($tag) ? "AND mg.tag IN(".phive('SQL')->makeIn($tag).")" : "";
    else
      $where .= "AND mg.tag = '$tag'";

    $where .= $this->blockCountry($block);

    $sel_game_tag_con = '';
    $join_game_tag = '';
    if(!empty($sub) && $sub != 'all') {
      $join_game_tag = "
        INNER JOIN game_tag_con
            ON mg.id = game_tag_con.game_id
            AND tag_id IN (
                SELECT id
                FROM game_tags
                WHERE alias = '$sub' AND game_tags.filterable = 1
            )";
      $sel_game_tag_con = ' , game_tag_con.id AS gtcid';
    }

    $limit  = (isset($offs) && isset($length)) ? 'LIMIT '. (int)$offs .',' . (int)$length : '';

    if(!empty($play_count_period)){
      $country 		= cuCountry();
      $countries	= explode(" ", phive("Config")->getValue('countries', 'gamechoose'));
      if(!empty($country) && !phive()->isEmpty($countries) && in_array($country, $countries))
        $country_join 	= "AND gc.country = '$country'";
      $gcache_tbl 	= phive()->isDate($play_count_period) ? 'game_cache' : ' game_month_cache';
      $sel = ", (
        select SUM(gc.played_times)
        from $gcache_tbl gc
        where mg.ext_game_name = gc.game_ref AND gc.day_date = '$play_count_period' $country_join
      ) as played_times_in_period";
    }

    $sel_jps = '';
    $join_jps = '';
    if ($should_join_jps) {
      [$sel_jps, $join_jps] = $this->getJoinJps();
    }

    list($sel_games_overrides, $join_games_overrides) = $this->getGamesOverridesJoin();
    $device_type = $where_device == "mg.device_type = 'flash'" ? 'flash' : 'html5';
    list($mobile_extra_select, $mobile_extra_join) = $this->overrideGameUrlByDeviceType($device_type);

    if (!empty($extra_where)) {
        $where .= $extra_where;
    }

    if(!empty($operator) && $operator != 'all'){
      $where .= " AND mg.operator = '$operator' ";
    }

    if($gps_via_gpr) {
        $gps_list = implode("','", phive('Casino')->getSetting('gps_via_gpr'));
        $where .= " AND mg.network IN ('$gps_list' )";
    }
    if (!empty($user_id)) {
        $fav = $this->favIds($user_id);
        if (!empty($fav) && !is_null($favorite)) {
            $favIds = implode(',', $fav);
            $where .= $favorite ? " AND mg.id IN ($favIds)" : " AND mg.id NOT IN ($favIds)";
        }
    }

    $order_by = phive('SQL')->removeQuery($order_by);

    $str = "SELECT DISTINCT mg.* $sel $sel_jps $sel_games_overrides $sel_game_tag_con $mobile_extra_select
      FROM {$this->table()} mg
      $join_game_tag
      $join_games_overrides
      $join_jps
      $mobile_extra_join
      $where
      $where_ops
      GROUP BY mg.id";

    // having removed ORDER BY from the query we need to append $order_by and $limit to keep the uniqueness of the redis key.
    $redisKey = "$str ORDER BY $order_by LIMIT $limit";

    if ($caching === true) {
        $res = phQget($redisKey);
        $this->addMultiplayGameFlagsToGameList($res); // TODO check if we can move this directly when we cache the results before phQset. ( but we need cache busting when we release)
        if(!empty($res)) {
            return $res;
        }
    }

    $res = phive('SQL')->doDb('replica')->loadArray($str);

    list($order_column, $order_sort) = explode(' ',strtolower($order_by));
    $order_columns = [$order_column, 'override_payout_extra_percent'];
    $order_sorts = [$order_sort, 'desc'];
    $res = phive()->sort2d($res, $order_columns, $order_sorts);

    // having the ordering done in PHP we cannot use $limit on the query, so we need to slice the array after the ordering is done.
    if(isset($offs) && isset($length)) {
        $res = array_slice($res, $offs, $length);
    }

    if ($caching === true) {
        phQset($redisKey, $res, rand(50000, 72000));
    }

    return $res;
  }

    function getRecentPlayed($user = '', $where_device = "games.device_type = 'flash'"){
        $user_id = uid($user);

    $sql = "SELECT DISTINCT games.* FROM wins
      LEFT JOIN micro_games AS games ON wins.game_ref = games.ext_game_name AND $where_device
      WHERE wins.user_id = $user_id
      ORDER BY wins.created_at DESC LIMIT 0,100";

    return phive('SQL')->sh($user_id, '', 'wins')->loadArray($sql);
  }

  function getById($id, $should_join_jp = false){
      [$default_sql] = $this->getGameByColCommon('mg.id', $id, null, $should_join_jp);

      return phive('SQL')->lb()->loadAssoc($default_sql);
  }

  function getByMobileId($mobile_id){
        [$default_sql] = $this->getGameByColCommon('mg.mobile_id', $mobile_id, null, false);

        return phive('SQL')->lb()->loadAssoc($default_sql);
    }

  function isEnabled(&$g){
    return (int)$g['enabled'] === 1;
  }

  function getGameByColCommon($id_col, $id, $device = null, $should_join_jp = false, $exclude_retired = false) {
      $where_device = '';
      if($device !== null && $device !== '' && $device !== false){
          $col = is_numeric($device) ? 'device_type_num' : 'device_type';
          $where_device = "AND $col = '$device'";
      }

      $where_retired = '';
      if($exclude_retired){
          $where_retired = " AND retired != 1";
      }

      $select_jp = '';
      $join_jp = '';

      if ($should_join_jp) {
          [$select_jp, $join_jp] = phive('MicroGames')->getJoinJps();
      }

      $default_sql = "
          SELECT mg.* $select_jp
          FROM {$this->table()} as mg $join_jp
          WHERE $id_col = '$id' $where_device $where_retired
      ";

      return [$default_sql, $where_device];
  }

    function getByGameId($id, $device = 0, $u_info = null, $should_join_jp = false) {
        list($default_sql, $where_device) = $this->getGameByColCommon('mg.game_id', $id, $device, $should_join_jp);
        $game = phive('SQL')->lb()->loadAssoc($default_sql);
        // If we can't find the game we check the override table for an identifier
        if(empty($game) && !empty($u_info)){
            $gid = $this->getGameIdByLaunchId($u_info, $id, $where_device);
            $game = empty($gid) ? [] : $this->getById($gid, $should_join_jp);
        }
        return $game;
    }

    /**
     * Gets the network of the game, this method is used for FE and BoS and therefore will NOT
     * and shall NOT return Gpr even if the GP is configured to be handled via / with the Gpr.
     * The Gpr has nothing to do with JS BoS events etc.
     *
     * @param array $game The game row from micro_games.
     *
     * @return string The network name.
     */
    function getNetworkName($game)
    {
        $network = strtolower(trim($game['network']));
        return $network != 'microgaming' ? ucfirst($network) : 'QuickFire';
    }

    function getNetworkModule($game){
        return phive('Casino')->getNetworkModule($game);
    }

  function getByGameRef($gref, $device = null, $u_info = null, $should_join_jp = false, $exclude_retired = false) {
      list($default_sql, $where_device) = $this->getGameByColCommon('ext_game_name', $gref, $device, $should_join_jp, $exclude_retired);

      $key = md5($default_sql);

      if(empty($this->cur_game[$key])){
          $game = phive('SQL')->lb()->loadAssoc($default_sql);
          // If we can't find the game we check the override table for an identifier
          if(empty($game) && !empty($u_info)){
              $gid  = $this->getGameIdByExtId($u_info, $gref, $where_device);
              $game = empty($gid) ? [] : $this->getById($gid, $should_join_jp);
          }
          $this->cur_game[$key] = $game;
      }
      return $this->cur_game[$key];
  }

  function nameByRef($gref, $device = null){
    $g = $this->getByGameRef($gref, $device);
    return $g['game_name'];
  }

  function getByGameUrl($id, $where_device = "device_type = 'flash'", $should_join_jp = false) {
      $select_jp = '';
      $join_jp = '';

      if ($should_join_jp) {
          [$select_jp, $join_jp] = phive('MicroGames')->getJoinJps();
      }

      $str = "
          SELECT mg.* $select_jp
          FROM {$this->table()} mg $join_jp
          WHERE game_url = '$id' AND retired != 1 AND $where_device
      ";

      return phive('SQL')->lb()->loadAssoc($str);
  }

  function getByNetwork($network, $only_active = false)
  {
      $where_active = $only_active ? " AND active = 1" : '';
      return phive("SQL")->lb()->loadArray("SELECT * FROM {$this->table()} WHERE network = '$network' $where_active");
  }

  function getGameRefById($gid, $where_device = "device_type = 'flash'"){
      return phive('SQL')->lb()->getValue("SELECT ext_game_name FROM {$this->table()} WHERE game_id = '$gid' AND $where_device");
  }

    /**
     * Get game_ref by id with mobile/desktop auto-detect
     *
     * @param $id
     * @return int|string
     */
    function getGameRefByIdWithAutoDeviceDetect($id)
    {
        return $this->getGameRefById($id, phive()->isMobile() ? "device_type = 'html5'" : "device_type = 'flash'");
    }

  function getGameTagByRef($gref){
    return phive('SQL')->lb()->getValue("SELECT tag FROM {$this->table()} WHERE ext_game_name = '$gref'");
  }

  function getGameJpContribByRef($gref){
      return phive('SQL')->lb()->getValue("SELECT jackpot_contrib FROM {$this->table()} WHERE ext_game_name = '$gref'");
  }

    /**
     * @param string|array $game_or_id
     * @param string $lang
     * @param string $where_device
     * @return string
     */
    public function getGameLang($game_or_id, $lang, $where_device = 'flash')
    {
        $default_lang = phive('Localizer')->getDefaultLanguage();
        if ($lang == $default_lang || empty($lang)) {
            return $default_lang;
        }
        if (!is_array($game_or_id)) {
            $game_or_id = $this->getByGameId($game_or_id, $where_device);
        }

        return strpos($game_or_id['languages'], $lang) !== false ? $lang : $default_lang;
    }

  function getGameLocale($id, $lang, $where_device = 'flash'){
    $glang = $this->getGameLang($id, $lang, $where_device);
    $locale = phive("Localizer")->getCountryValue('setlocale', $glang);
    return str_replace('.utf8', '', empty($locale) ? 'en_US' : $locale);
  }

  function getIdWithGid($id, $where_device = 'flash'){
    $game = $this->getByGameId($id, $where_device);
    return $game['id'];
  }

  function getGamesByIds($gids, $where_device = 'flash'){

    $gids_str = phive('SQL')->makeIn($gids);
    $str = "SELECT id, game_id FROM micro_games WHERE game_id IN($gids_str) AND device_type = '$where_device' GROUP BY game_id";

    return phive('SQL')->lb()->loadArray($str, 'ASSOC', 'game_id');
  }

  function favIds($uid){
    if (empty($uid)) {
        return [];
    }
    $str = "SELECT * FROM users_games_favs WHERE user_id = $uid";
    return phive('SQL')->sh($uid, '', 'users_games_favs')->load1DArr($str, 'game_id');
  }

  function isFavorite($uid, $gid){
    $gid = is_array($gid) ? $gid['id'] : $gid;
    $uid = intval($uid);
    return phive("SQL")->sh($uid)->loadAssoc("", 'users_games_favs', array('user_id' => $uid, 'game_id' => $gid));
  }

  function sortFavs($sql, $key, $games, $tbl, $uid){
      $stats = phive('SQL')->sh($uid, '', $tbl)->loadArray($sql, 'ASSOC', 'game_ref');
    foreach($games as $gref => &$g)
      $g[$key] = $stats[$gref][$key];
    return phive()->sort2d($games, $key, 'desc');
  }

  function getFavorites($uid, $extra = null, $sort_by = 'added-desc', $device_type = 'flash', $should_join_jps = false) {
    if (empty($uid)) {
        return [];
    }

    $extra    = empty($extra) ? '' : " AND $extra";
    $map      = array('added-desc' => "favdate DESC", 'added-asc' => "favdate ASC");
    $order_by = $map[$sort_by];

    if(!empty($order_by)) {
      $order_by = "ORDER BY $order_by";
    }

    if ($should_join_jps) {
      // firstly get ext_game_name from shard (micro_jps table exists on master only, so we need intermediate step)
      $sql = "
        SELECT mg.id, mg.ext_game_name, favs.created_at AS favdate
        FROM micro_games mg
        INNER JOIN users_games_favs AS favs ON favs.game_id = mg.id AND favs.user_id = {$uid}
        WHERE mg.active = 1
        $extra
        $order_by";

      $games = phive('SQL')->sh($uid, '', 'users_games_favs')->loadArray($sql, 'ASSOC', 'id');
      $game_ids_in = phive('SQL')->makeIn(array_keys($games));
      [$sel_jps, $join_jps] = $this->getJoinJps();

      // then get the same games on master joined with jackpots
      $sql = "
        SELECT mg.* $sel_jps
        FROM micro_games mg
        $join_jps
        WHERE mg.active = 1 AND mg.id IN ($game_ids_in)
        GROUP BY mg.id";

      $games = phive("SQL")->loadArray($sql, 'ASSOC', 'ext_game_name');
    } else {
      $sql = "
        SELECT mg.*, favs.created_at AS favdate
        FROM micro_games mg
        INNER JOIN users_games_favs AS favs ON favs.game_id = mg.id AND favs.user_id = {$uid}
        WHERE mg.active = 1
        $extra
        $order_by";

      $games = phive("SQL")->sh($uid, '', 'users_games_favs')->loadArray($sql, 'ASSOC', 'ext_game_name');
    }

    if($sort_by == 'most-played'){
      $games = $this->sortFavs(
        "SELECT *, COUNT(*) AS ptimes FROM users_daily_game_stats WHERE user_id = $uid GROUP BY game_ref ORDER BY `ptimes` DESC LIMIT 0,100",
        'ptimes',
        $games,
        'users_daily_game_stats',
        $uid
      );
    }

    if($sort_by == 'profit'){
      $games = $this->sortFavs(
        "SELECT *, SUM(wins) - SUM(bets) AS profit FROM users_daily_game_stats WHERE user_id = $uid GROUP BY game_ref ORDER BY `profit` DESC LIMIT 0,100",
        'profit',
        $games,
        'users_daily_game_stats',
        $uid
      );
    }

    // Add multi games flags to list of games
    $this->addMultiplayGameFlagsToGameList($games);

    return $games;
  }

  function insertFavorite($uid, $gid){
    return phive("SQL")->sh($uid, '', 'users_games_favs')->insertArray('users_games_favs', array('user_id' => $uid, 'game_id' => $gid));
  }

  function deleteFavorite($uid, $gid){
    return phive("SQL")->delete('users_games_favs', array('user_id' => $uid, 'game_id' => $gid), $uid);
  }

    /**
     * @api
     *
     * @param int $uid
     * @param int $gid
     *
     * @return string
     */
  public function toggleFavorite($uid, $gid){
    $uid = (int)$uid;
    $gid = (int)$gid;
    $fav = $this->isFavorite($uid, $gid);
    if(empty($fav)){
      $game = $this->getById($gid);
      uEvent('favourite', '', $game['game_name'], $game['game_id'], $uid);
      $this->insertFavorite($uid, $gid);
      return 'inserted';
    }else{
      $this->deleteFavorite($uid, $gid);
      return 'deleted';
    }
  }

  function getFavored($limit, $device_type = 'flash', $should_join_jps = false) {
    $limit = empty($limit) ? '' : " LIMIT 0,$limit ";
    [$sel_games_overrides, $join_games_overrides] = $this->getGamesOverridesJoin();

    $sel_jps = '';
    $join_jps = '';
    if ($should_join_jps) {
      [$sel_jps, $join_jps] = $this->getJoinJps();
    }

    return phive("SQL")->shs('merge', 'cnt', 'desc', 'users_games_favs')->loadArray("
      SELECT mg.*, COUNT(ugf.game_id) AS cnt $sel_games_overrides $sel_jps
      FROM users_games_favs ugf
      INNER JOIN micro_games AS mg ON mg.id = ugf.game_id AND mg.device_type = '$device_type'
      $join_games_overrides $join_jps
      GROUP BY game_id ORDER BY cnt DESC $limit, override_payout_extra_percent desc");
  }

    /**
     * Will return the list of micro_games.id of the last played games from the cookie.
     * The list on desktop and mobile are different on purpose.
     *
     * @param $key "flash_last_played | mobile_last_played"
     * @param $should_join_jps bool
     * @param $where_extra string|null
     * @param int|null $user_id
     * @return array
     */
    function getLastPlayed(
        string $key,
        bool $should_join_jps = false,
        string $where_extra = null,
        ?int $user_id = null
    ): array
    {
        $ids_array = $this->getLastPlayedGamesFromMemory($key, $user_id);
        if (empty($ids_array)) {
            return [];
        }

        $ids = implode(',', array_map(function($id) {
            return phive("SQL")->escape(intval($id));
        }, $ids_array));

        $device_type = $key === 'mobile_last_played' ? 'html5' : 'flash';
        list($mobile_extra_select, $mobile_extra_join) = $this->overrideGameUrlByDeviceType($device_type);

        $sel_jps = '';
        $join_jps = '';
        if ($should_join_jps) {
            [$sel_jps, $join_jps] = $this->getJoinJps();
        }

        $sql = "
            SELECT
                mg.*
                {$mobile_extra_select}
                {$sel_jps}
            FROM
                micro_games mg
                {$mobile_extra_join}
                {$join_jps}
            WHERE
                mg.id IN($ids)
                AND mg.active = 1
            ";

        if ($where_extra) {
            $sql .= "AND " . $where_extra;
        }

        $active_games = phive("SQL")->lb()->loadArray($sql, 'ASSOC', 'id');
        $active_games_ids = array_keys($active_games);

        // if $ids_array and $active_games_ids lengths are diffrent
        // we need to get only common ids and keep its order
        if (count($ids_array) != count($active_games_ids)) {
            $ids_array = array_intersect($ids_array, $active_games_ids);
            $this->updateLastPlayedGamesInMemory($key, $ids_array, $user_id);
        }

        // Added multi play games flag to the games list
        $this->addMultiplayGameFlagsToGameList($active_games);
        return phive()->orderKeysBy($active_games, $ids_array);
    }

    /**
     * Update the (flash|mobile)_last_played cookie adding the currently selected game['id']
     * at the beginning of the list
     *
     * @param $game
     * @param $key
     */
    function cookieLastPlayed($game, $key)
    {
        if (empty($_COOKIE[$key])) {
            $last_played = array($game['id']);
        }
        else {
            $last_played = phive()->remEmpty(explode(',', $_COOKIE[$key]));
            array_unshift($last_played, $game['id']);
        }
        setCookieSecure($key, implode(',', array_unique($last_played)), time() + 60 * 60 * 24 * 300);
    }

    function getThumb($g)
    {
        $filepath = "{$g['game_id']}.jpg";
        return fupUri($filepath, true,  "no_pic.jpg");
    }

    function carouselPic($g, $is_tall_version = false)
    {
        $game_id  = is_array($g) ? $g['game_id'] : $g;
        $postfix = $is_tall_version ? 'c2' : 'c';
        $filepath = "thumbs/{$game_id}_{$postfix}.jpg";

        return fupUri($filepath, true, 'thumbs/nopic_c.png');
    }

    function tagIcon($g)
    {
        $tag = is_array($g) ? $g['tag'] : $g;
        $filepath = "{$tag}_icon.png";
        return fupUri("{$tag}_icon.png", true, 'default_tag_icon.png');
    }

  function getAllTags($where_device = "device_type = 'flash'"){
      return phive('SQL')->lb()->load1DArr("SELECT tag FROM {$this->table()} WHERE $where_device GROUP BY tag ORDER BY tag", 'tag');
  }

    /**
     * Return games filtered by tags (if passed) and device type.
     * The game are sorted by latest added tag.
     * (Ex. adding new tag to game will move that higher in the mobile homepage slider list)
     *
     * @param array $subs - array of "game_tags.alias" to filter with.
     * @param string $device_type - default "flash" (desktop), can be either "flash" or "html5" (mobile)
     * @param false $block - if false will skip blocked/included_countries check
     * @param bool $only_filterable - default "true" only tags with column filterable = 1 will be returned, if false any tag is returned
     * @param bool $should_join_jps - join jackpot value for games
     * @return mixed
     */
    function groupBySub($subs = [], $device_type = "html5", $block = false, $only_filterable = true, $should_join_jps = false, $gps_via_gpr = false) {
        $where_block = $this->blockCountry($block);
        $where_sub = empty($subs) ? '' : " AND gt.alias IN(" . phive("SQL")->makeIn($subs) . ") ";
        $where_filterable = $only_filterable ? " AND gt.filterable = 1 " : "";

        if($gps_via_gpr) {
            $gps_list = phive('Gpr')->getGprFilter();
            $where_filterable .= $gps_list;
        }

        $select_jps = '';
        $join_jps = '';
        if ($should_join_jps) {
            [$select_jps, $join_jps] = phive('MicroGames')->getJoinJps();
        }

        $str = "
            SELECT
                mg.*, gt.*, gtc.id gtcid $select_jps
            FROM
                micro_games mg
                INNER JOIN game_tag_con gtc ON mg.id = gtc.game_id
                INNER JOIN game_tags gt ON gtc.tag_id = gt.id
                $join_jps
            WHERE
    		mg.active = 1 AND
            	mg.device_type = '$device_type'
                $where_block
                $where_sub
                $where_filterable
            GROUP BY mg.id
            ORDER BY
                gtcid DESC
        ";
        return phive()->group2d(phive("SQL")->loadArray($str), 'alias');
    }

    /**
     * Return all subtag (game_tags) with at least 1 game assigned (game_tag_con)
     * for the selected device type
     *
     * @param string|array $tag - empty|"all"|['all'] will not apply filter, otherwise filtering by tag(s)
     * @param string $device_type - default "flash" (desktop), can be either "flash" or "html5" (mobile)
     * @param bool $only_filterable - default "true" only tags with column filterable = 1 will be returned, if false any tag is returned
     * @param string $operator
     * @return array
     */
    public function getAllSubTags($tag = '', $device_type = 'flash', $only_filterable = true, $operator = ''): array
    {
        if (is_array($tag)) {
            $where = "IN (" . phive('SQL')->makeIn($tag) . ")";
        } else {
            $where = "= '$tag'";
        }
        $where_tag = (empty($tag) || $tag == 'all' || $tag == array('all')) ? "" : " AND tag $where ";
        $country = licJur();
        $where_filterable = $only_filterable ? "AND game_tags.filterable = 1" : "";
        $operator = phive('SQL')->realEscape($operator);
        $where_operator = empty($operator) || $operator == 'all' ? "" : "AND operator = '$operator'";

        $str = "
            SELECT
                DISTINCT alias
            FROM
                game_tags
            WHERE
                id IN (
                    SELECT tag_id
                    FROM game_tag_con
                    WHERE game_id IN (
                        SELECT id
                        FROM micro_games
                        WHERE device_type = '$device_type' {$where_tag} {$where_operator}
                    )
                )
                AND game_tags.excluded_countries NOT LIKE '%{$country}%'
                $where_filterable
            ORDER BY
                alias
        ";

        $res = phQget($str);
        if (!empty($res)) {
            return $res;
        }

        $res = phive('SQL')->load1DArr($str, 'alias');

        phQset($str, $res, 7200);

        return $res;
    }

  function getGroupedByTag($map = array(), $where_device = "device_type = 'flash'"){
    $rarr = array();
    foreach($this->getAllTags($where_device) as $tag){
      if(!empty($map[$tag])){
        $rarr[ $map[$tag] ] = array_merge((array)$rarr[ $map[$tag] ], $this->getTaggedBy($tag));
      }else
      $rarr[$tag] = array_merge((array)$rarr[$tag], $this->getTaggedBy($tag));
    }
    return $rarr;
  }

  function getUrl($game_id, $g = null, $with_lang = true, $play = false, $where_device = 'flash'){
    $g 		= empty($g) ? $this->getByGameId($game_id, $where_device) : $g;
    $extra	= $play ? "?play=true" : '';
    $lang 	= $with_lang ? phive('Localizer')->getNonSubLang() : '';
    return $lang . "/games/".$g['game_url']."/$extra";
  }

  function getAllJpIds(){
    return phive('SQL')->load1DArr("SELECT * FROM micro_jps", 'jp_id');
  }

  function getJpSum($where_extra = ''){
    $jps = phive('SQL')->loadArray("SELECT mj.* FROM micro_jps mj, {$this->table} mg WHERE mj.module_id = mg.module_id $where_extra GROUP BY mj.jp_value");
    $sum = 0;
    foreach($jps as $jp)
    $sum += $jp['jp_value'];
    return $sum / 100;
  }

  function getAllJps($where = '', $limit = 10000){
    if(!empty($where))
      $where = " WHERE $where";
    $str = "SELECT * FROM micro_jps $where ORDER BY jp_value DESC LIMIT 0,$limit";
    return phive('SQL')->loadArray($str);
  }

  function getNonMicroJpsGames($network){
    return phive("SQL")->loadArray("SELECT mj.*, mg.* FROM micro_jps mj, micro_games mg WHERE mj.network = '$network' AND mj.ext_game_name = mg.ext_game_name");
  }

  function getJp($value, $key = 'id'){
    return phive('SQL')->loadObject("SELECT * FROM micro_jps WHERE $key = '$value'");
  }

  function getJpsGrouped($where_device = "device_type = 'flash'"){
    return phive()->group2d($this->getAllJpsGames($where_device), 'jp_id');
  }

  function fixGameName($name){
    $name = str_replace('-Instant', '', $name);
    return str_replace('- Instant', '', $name);
  }

  function getProgressives($where_device = "device_type = 'flash'"){
    if(!empty($where_device))
      $where = "AND $where_device";
    return phive('SQL')->lb()->loadArray("SELECT * FROM {$this->table} WHERE jackpot_contrib > 0 $where");
  }

  function getBetSumPerGameDay($game_ref, $sdate, $edate, $bonus_bet){
    $str = "SELECT *, DATE(created_at) AS created_date, SUM(amount) AS amount_total FROM bets
        WHERE created_at >= '$sdate'
        AND created_at <= '$edate'
        AND game_ref = '$game_ref'
        AND bonus_bet = $bonus_bet
        GROUP BY created_date";
    return phive('SQL')->shs('merge', '', null, 'bets')->loadArray($str);
  }

  function getJpContribStatsByDayGame($sdate, $edate, $bonus_bet = 0, $where_device = ""){
    $rarr 	= array();
    foreach($this->getProgressives($where_device) as $game){
      $days = $this->getBetSumPerGameDay($game['ext_game_name'], $sdate, $edate, $bonus_bet);
      foreach($days as $bet)
      $rarr[$game['game_name']][$bet['created_date']] = round($bet['amount_total'] / (1 - $game['jackpot_contrib']));
    }
    return $rarr;
  }

  function getJpContribStatsByDay($sdate, $edate, $bonus_bet = 0, $where_device = ""){
    $rarr = array();
    foreach($this->getJpContribStatsByDayGame($sdate, $edate, $bonus_bet, $where_device) as $g => $c){
      foreach($c as $date => $amount)
      $rarr[] = array('game' => $g, 'date' => $date, 'amount' => $amount);
    }
    return phive()->sort2d($rarr, 'date');
  }

  function getJpContribStats($sdate, $edate){
    $str = "SELECT *, DATE(created_at) AS created_date FROM bets
               WHERE created_at >= '$sdate'
        AND created_at <= '$edate'
        AND game_ref IN (SELECT ext_game_name FROM {$this->table} WHERE jackpot_contrib > 0)";
    return phive('SQL')->shs('merge', '', null, 'bets')->loadArray($str);
  }

    /**
     * @param string $where_extra
     * @param string $where_device
     * @param string $group_by
     * @param array $pagination
     * @param int $cache
     * @return mixed
     */
    public function getAllJpsGames($where_extra = '', $where_device = "gms.device_type = 'flash'", $group_by = '', $pagination = [], $cache = 0) {
        $where = " AND $where_device ";
        if (!empty($where_extra)) {
            $where .= " AND $where_extra ";
        }
        $limit = '';
        if (!empty($pagination)) {
            $limit .= "LIMIT " . implode(',', $pagination);
        }

        $joinOnModuleId = $this->selectJps('gms.module_id', $where);
        $joinOnExtGameName = $this->selectJps('gms.ext_game_name', $where);

        $str = "
            $joinOnModuleId
            UNION ALL
            $joinOnExtGameName
            $group_by
            ORDER BY jp_value DESC
            $limit";

        if (empty($cache)) {
            return phive('SQL')->loadArray($str);
        }

        $res = phQget($str);
        if (empty($res)) {
            $res = phive('SQL')->loadArray($str);
            if (count($res) > 1) {
                phQset($str, $res, $cache);
            }
        }

        return $res;
    }

    private function selectJps(string $joinOnColumn, string $where)
    {
        return "SELECT jps.*, gms.*
                FROM micro_jps jps
                JOIN micro_games gms ON jps.module_id = $joinOnColumn
                WHERE jps.module_id != ''
                AND gms.active = 1
                AND jps.jp_value > 1000
                AND jps.tmp = 0
                $where";
    }

  function statsNumCols(){
    return array('bets', 'wins', 'jp_contrib', 'overall_gross', 'frb_wins', 'jp_fee', 'op_fee', 'site_gross');
  }

  function statsHeadlines($all = true){
    $nums = array('Bets', 'Wins', 'JP Ded.', 'Overall Gross', 'FRB Wins', 'JP Fee', 'Op. Fees', 'Site Gross');
    if(!$all)
      return $nums;
    return array_merge(array('Date', 'Game', 'Device Type', 'Played Times'), $nums);
  }

  function getDailyStatsCols($prefix = ''){
    $tmp = array("frb_wins", "op_fee", "jp_contrib", "bets", "wins", "jp_fee", "frb_ded");
    if(empty($prefix))
      return $tmp;
    return array_map(function($el) use ($prefix){ return "$prefix.$el"; }, $tmp);
  }

  function getTax($start_date, $end_date, $countries, $network = '', $currency = ''){
    $where_network = empty($network) ? '' : "AND ns.network = '$network'";
    if(empty($currency)){
      $join = "LEFT JOIN currencies AS c ON c.code = ns.currency";
      $postfix = " / c.multiplier";
    }else{
      $where_currency = "AND ns.currency = '$currency'";
    }

    $where = "WHERE ns.date >= '{$start_date}' AND ns.date <= '{$end_date}' AND ns.country IN($countries)";

    $num_cols = phive('SQL')->makeSums(array('ns.bets', 'ns.wins', 'ns.gross', 'ns.rewards', 'ns.tax', 'ns.jp_contrib'), $postfix);
    $str = "SELECT $num_cols, ns.network, ns.country, ns.date, 0 AS vat, 0 AS vat_percent, ns.tax_percent, 0 AS cnt
            FROM network_stats ns
            $join
            $where
            $where_network
            $where_currency
            GROUP BY ns.country";

    $res = phive('SQL')->readOnly()->loadArray($str, 'ASSOC', 'country');

    $str = "SELECT ns.user_id, ns.country FROM users_daily_game_stats ns $where $where_network GROUP BY ns.user_id";

      //$actives = phive('SQL')->shs('merge', '', null, 'users_daily_game_stats')->loadArray($str);
      $actives = phive('SQL')->readOnly()->loadArray($str);

    //print_r($actives);

    foreach($actives as $a){
      if(!empty($res[$a['country']]))
        $res[$a['country']]['cnt']++;
    }

    return $res;
  }

  function getGroupedStats($start_date, $end_date, $group_by, $where_extra = "", $multiplier = ""){
    if($multiplier){
      $join_cur = "LEFT JOIN currencies AS cur ON cur.code = gs.currency";
      $multiplier = " / cur.multiplier";
    }

    $cols = $this->getDailyStatsCols('gs');
    $sums = phive("SQL")->makeSums($cols, $multiplier);

    $str = "SELECT
        $sums,
        SUM(gs.bets $multiplier) - SUM(gs.wins $multiplier) - SUM(gs.jp_contrib $multiplier) - SUM(gs.frb_ded $multiplier) AS overall_gross,
        SUM(gs.bets $multiplier) - SUM(gs.wins $multiplier) - SUM(gs.jp_fee $multiplier) - SUM(gs.frb_wins $multiplier) - SUM(gs.jp_contrib $multiplier) - SUM(gs.op_fee $multiplier) AS site_gross,
        gs.network
      FROM users_daily_game_stats gs
      $join_cur
      WHERE `date` >= '{$start_date}' AND `date` <= '{$end_date}'
      $where_extra
      GROUP BY $group_by";

      //return phive()->sum3dAssoc(phive("SQL")->shs(false, '', null, 'users_daily_game_stats')->loadArray($str, 'ASSOC', $group_by));
      return phive("SQL")->readonly()->loadArray($str, 'ASSOC', $group_by);
  }

  function getDailyGameStats($start_date, $end_date, $group_by = 'gs.date', $where_extra = '', $join_users = false, $device_type = '', $uid = false, $join_province = ''){

    if($device_type != ''){
      $device_type = phive("QuickFire")->getDeviceNum($device_type);
      $where_extra .= " AND gs.device_type = $device_type ";
    }

    $select = '';
    if($join_users){
      $select .= " , u.* ";
      $join 	 = " LEFT JOIN users AS u ON u.id = gs.user_id ";
    }

    if ($join_users && !empty($join_province)) {
      $join   .= $join_province;
    }

    $sums_arr = ['bets', 'wins', 'frb_wins', 'op_fee', 'jp_contrib', 'jp_fee', 'frb_ded'];
    $sums = phive("SQL")->makeSums($sums_arr, '', 'gs');

    $str = "SELECT $sums, SUM(gs.bets) - SUM(gs.wins) AS gross, gs.game_ref, gs.date, gs.device_type,
          SUM(gs.bets) - SUM(gs.wins) - SUM(gs.jp_contrib) - SUM(gs.frb_ded) AS overall_gross,
          SUM(gs.bets) - SUM(gs.wins) - SUM(gs.jp_fee) - SUM(gs.frb_wins) - SUM(gs.jp_contrib) - SUM(gs.op_fee) AS site_gross
          $select
          FROM users_daily_game_stats gs
          $join
          WHERE gs.date >= '$start_date' AND gs.date <= '$end_date' $where_extra
          GROUP BY $group_by ORDER BY $group_by ASC";

      $dstats = phive("SQL")->readOnly()->shs([
          'action' => 'sum',
          'do_only' => array_merge($sums_arr, ['gross', 'overall_gross', 'site_gross'])
      ], '', null, 'users_daily_game_stats')->loadArray($str, 'ASSOC', array('game_ref', 'device_type'));

    $str = "SELECT game_ref, SUM(played_times) AS played_times, device_type_num FROM game_cache
        WHERE day_date >= '$start_date'
        AND day_date <= '$end_date'
        GROUP BY game_ref, device_type_num";

    if ($uid) {
      $str = "SELECT game_ref, COUNT(id) AS played_times, device_type_num FROM users_game_sessions
        WHERE stime >= '{$start_date}'
        AND etime <= '{$end_date}'
        AND user_id = '$uid'
        GROUP BY game_ref, device_type_num";

        $cstats = phive("SQL")->readOnly()->sh($uid, '', null, 'users_game_sessions')->loadArray($str, 'ASSOC', array('game_ref', 'device_type_num'));
    }else
        $cstats = phive("SQL")->readOnly()->loadArray($str, 'ASSOC', array('game_ref', 'device_type_num'));

    phive()->addCol2d($cstats, $dstats, array('played_times' => 'played_times', 'game_ref' => 'game_ref', 'device_type_num' => 'device_type_num'), false, false, false);

    $games = phive("SQL")->readOnly()->loadArray("SELECT * FROM micro_games", 'ASSOC', 'ext_game_name');

    foreach($dstats as &$s){
      $s['game_name'] = $games[$s['game_ref']]['game_name'];
      if(!isset($s['device_type']))
        $s['device_type'] = $s['device_type_num'];
    }

    return $dstats;

  }

    /*
  function calcRaceSpins($a, $where_games, $where_tags, $min_amount = '', $max_amount = '', $recalc = false){
    //$where_amount = empty($a['min_amount']) ? "" : " AND bets.amount >= ({$a['min_amount']} * currencies.mod) ";
    $where_amount = empty($min_amount) ? "" : " AND bets.amount >= ($min_amount * currencies.mod) ";
    $where_max_amount = empty($max_amount) ? "" : " AND bets.amount <= ($max_amount * currencies.mod) ";
    $str = "SELECT COUNT(*) as count, bets.*, users.username, users.firstname, users.id, currencies.mod AS cur_mod FROM bets
          LEFT JOIN users ON users.id = bets.user_id
          LEFT JOIN currencies ON currencies.code = bets.currency
          WHERE `created_at` >= '{$a['sdate']} 00:00:00'
          AND `created_at` <= '{$a['edate']} 23:59:59'
          AND bonus_bet = 0
          $where_amount
          $where_max_amount
          $where_games
          $where_tags
          GROUP BY bets.user_id";
    if($recalc == true)
      echo $str;
    return phive('SQL')->loadArray($str, 'ASSOC', 'user_id');
  }
     */

    /*
  function calcRacePoints($a, $where_games, $where_tags, $recalc = false){
    $res    = array();
    $levels = array();
    $tmp    = explode('|', $a['levels']);

    while(true){
      list($min_amount, $points) = explode(':', current($tmp));
      list($max_amount, $na) = explode(':', next($tmp));
      $levels[] = array('min' => $min_amount, 'max' => empty($max_amount) ? '' : $max_amount - 1, 'points' => $points);
      if(phive()->isEmpty($max_amount))
        break;
    }

    foreach($levels as $lvl){
      foreach($this->calcRaceSpins($a, $where_games, $where_tags, $lvl['min'], $lvl['max'], $recalc) as $uid => $r){
        $res[$uid]['count'] += $r['count'] * $lvl['points'];
        $res[$uid]['username'] = $r['username'];
        $res[$uid]['firstname'] = $r['firstname'];
        $res[$uid]['user_id'] = $r['user_id'];
        $res[$uid]['cur_mod'] = $r['cur_mod'];
      }
    }

    return $res;
  }
    */

  function recalcGameUserStats($date, $make_tmp = true){

      if($make_tmp)
          phive('Casino')->makeBetWinTmpForDate($date);

      /** @var SQL $sql */
      $sql = phive('SQL');

      $sql->shs(false, '', null, 'users_daily_game_stats')->query("DELETE FROM users_daily_game_stats WHERE `date` = '$date'");
      $sql->query("DELETE FROM network_stats WHERE `date` = '$date'");

      if($sql->isSharded('users_daily_game_stats')){
          $sql->loopShardsSynced(function($db, $shard, $id) use($date){
              $this->calcGameUserStats($date, $db);
          });
          phive('UserHandler')->aggregateUserStatsTbl('users_daily_game_stats', $date);
      }else
          $this->calcGameUserStats($date);
      $this->calcNetworkStats($date);
  }

  function wsOnWin($gid, $amount, $uid){
    $cur_game = $this->getById($gid);
    uEvent('woningame', $amount, $cur_game['game_name'], $cur_game['game_id'], $uid);
  }

    function fixNetwork($date, $db = ''){
        $db = empty($db) ? phive('SQL') : $db;
        $rows = $db->loadArray("SELECT * FROM users_daily_game_stats WHERE network = '' AND `date` = '$date'");
        foreach($rows as $r){
            $g = $this->getByGameRef($r['game_ref'], $r['device_type']);
            if(empty($g))
                $g = $this->getByGameRef($r['game_ref'], null);
            $r['network'] = $g['network'];
            $db->save('users_daily_game_stats', $r);
        }
    }

  function getGameUserSql($date, $type = 'bets', $join_games = false, $frb_only = false, $tbl_extra = '_tmp'){
    if($type == 'bets'){
      $contrib = 'SUM(bt.jp_contrib) AS jp_contrib,';
      $loyalty = 'SUM(bt.loyalty) AS paid_loyalty,';
    }

    if($join_games == true){
      $join = "LEFT JOIN (SELECT * FROM micro_games GROUP BY ext_game_name, device_type_num) AS mg ON mg.ext_game_name = bt.game_ref AND mg.device_type_num = bt.device_type";
      $select = ", mg.network AS network";
    }

    $where = "WHERE 1";

    if($type == 'wins')
      $where .= " AND bt.award_type != 4";

    if($frb_only)
      $where .= " AND bt.bonus_bet = 3";
    else
      $where .= " AND bt.bonus_bet != 3";

    $str = "SELECT DISTINCT
          bt.id,
          SUM(bt.amount) AS $type,
          COUNT(*) AS {$type}_count,
          DATE('$date') AS `date`,
          bt.game_ref,
          bt.currency,
          bt.user_id,
          bt.device_type,
          SUM(bt.op_fee) AS op_fee,
          $loyalty
          $contrib
          u.username,
          u.firstname,
          u.lastname,
          u.affe_id,
          u.country
          $select
        FROM {$type}{$tbl_extra} bt
        LEFT JOIN users AS u ON u.id = bt.user_id
        $join
        $where
        GROUP BY bt.user_id, bt.game_ref, bt.device_type";
    //echo $str."\n";
    //exit;
    return $str;
  }

  function filterBlocked($arr, $country){
    $games = phive('SQL')->lb()->loadArray("SELECT * FROM micro_games", 'ASSOC', 'game_id');
    $me = $this;
    return array_filter($arr, function($b) use ($games, $country, $me){
      if(empty($b['game_id']))
        return true;
      return !$me->isBlocked($games[$b['game_id']], $country);
    });
  }

    function isBlocked(&$game, $country = null, $checkByCountry = false){
        /** @var DBUser $u */

        $u = cu();
        if(empty($u) && !$checkByCountry)
            return false;

        if (!empty($u) && in_array($u->getId(), $this->getSetting('unblock_games_for_test_accounts'))) {
            return false;
        }

        $cur_country  = $country ?? null;
        $cur_province = false;

        if ($u !== false) {
            $cur_country = empty($country) ? $u->getCountry() : $country;
            $cur_province = $u->getProvince();
        }

        $select_province = licSetting('require_main_province') ? 'blocked_provinces,' : '';
        if (empty($cur_country)) {
            $cur_country = null; // Remove "needle is empty" warning.
        }
        // Check if current country is blocked in one of db entries where mobile_id = $game['id']
        if(!empty($cur_country)) {
            $game_id = $game['id'] ? $game['id'] : false;
            $parent_games = $game_id ? phive('SQL')->loadArray("SELECT game_id, blocked_countries, $select_province included_countries FROM {$this->table()} WHERE `mobile_id` = '{$game_id}'",
                "ASSOC", 'game_id') : false;
            if ($parent_games) {
                foreach ($parent_games as $parent_game) {
                    if ( $this->checkIsGameBlocked($cur_country, $cur_province, $parent_game)) {
                        return true;
                    }
                }
            }
        }

        if($this->checkIsGameBlocked($cur_country, $cur_province, $game))
            return true;
        return false;
    }

    function checkIsGameBlocked($country, $province, $game) {
        $is_country_blocked = $this->isCountryBlocked($country, $game['blocked_countries']);
        $is_country_included = $this->isCountryIncluded($country, $game['included_countries']);
        $is_province_blocked = $this->isProvinceBlocked($province, $game['blocked_provinces']);
        return $is_country_blocked || $is_country_included || $is_province_blocked;
    }

    function isProvinceBlocked($province, $blocked_provinces) {
        return licSetting('require_main_province') && $province && strpos($blocked_provinces, $province) !== false;
    }

    function isCountryBlocked($country, $blocked_countries) {
        return strpos($blocked_countries, $country) !== false;
    }

    function isCountryIncluded($country, $included_countries) {
        return !empty($included_countries) && strpos($included_countries, $country) === false;
    }

    function uniqueGameId(&$g){
        return $g['device_type_num'].'-'.$g['ext_game_name'];
    }

    function blockMisc($user = ''){
        $user = empty($user) ? cuPl() : $user;
        if(empty($user))
            return false;
        if(!empty($user) && cuCountry($user->getId(), false) == 'AU'){
            //$cnt = phive('Cashier')->getDepositCount($user->getId(), '', " AND `timestamp` BETWEEN '2017-01-01 00:00:00' AND '2017-09-07 00:00:00'");
            //if(empty($cnt) && !$user->hasSetting('bypass-au-playcheck'))
            if(!$user->hasSetting('bypass-au-playcheck'))
                return true;
        }
        return false;
    }

    function onPlay($game, $args)
    {
        if (phive()->isMobile() && isset($_REQUEST['eid'])) {
            $t_entry = phive('Tournament')->entryById($_REQUEST['eid'], $args['user_id']);
            $url = '/' . $args['lang'] . '/' . phive('Tournament')->getSetting('mobile_bos_url') . '/';
            if (!empty($t_entry)) {
                $url = "{$url}/game-mode/{$t_entry['t_id']}";
            }
            phive('Redirect')->to($url, cLang());
        }
        if (empty($args['type'])) {
            $args['type'] = 'flash';
        }

        $args[GprFields::CLIENT] = phive()->getCurrentDeviceType();

        $show_demo = $args['show_demo'] ?? false;

        $this->cookieLastPlayed($game, "{$args['type']}_last_played");

        if(empty($game)) {
            $this->logForFixedUsers('no game');
            die('no game');
        }

        $url = null;
        $user = empty($args['uid']) ? cuPl() : cu($args['uid']);

        list($t_uid, $t_eid) = explode('e', $args['user_id']);

        $t_entry = phive('Tournament')->entryById($t_eid, $t_uid);
        $t = phive('Tournament')->getByEntry($t_entry);

        /*
           TODO
           popup with you have to play from outside australia:
           everybody who have made a deposit between 01 jan and 07 sept 2017 -> should not get the error message
         */
        if (lic('noDemo', [$show_demo, $game], $user) && $t == null) {
            $url = llink('/play-block/');
        } else if ($this->isBlocked($game)) {
            $url = llink('/blocked-country/');
        } else if ($this->blockMisc($user)) {
            $url = '/country-ip-restriction/';
        } elseif (!empty($user)) {
            $ss = $user->getAllSettings('', true);

            if (lic('shouldRedirectToVerificationModal', [$user], $user)) {
                $url = lic('handleRgLimitPopupRedirection', [$user, $args['type'], 'gbg_verification']);
            } elseif ($user->isPlayBlocked()) {
                if ((int)$ss['id_scan_failed'] === 1) {
                    $redirect_url = $user->accUrl('documents');
                    $url = phive('Licensed')->goToUrlBeforePlay($user, $args['type'], lic('getDocumentsUrl', [$user], $user), $redirect_url);
                } else {
                    $url = llink('/play-block/');
                }
            } elseif (!$user->isTestAccount() && (int)$ss['experian_block'] === 1)  {
                $url = llink('/identity-block/');
            } elseif ((int)$ss['tac_block'] === 1 || !$user->hasCurTc() ) {
                phive()->dumpTbl('tac-block', [$ss['tac_block'], !$user->hasCurTc(), $user->getSetting('tc-version'), lic('getTermsAndConditionVersion'), $game]);
                $url = phive('Casino')->getBasePath(null, null, true).'tac-block/';
            } else {
                $url = lic('beforePlay', [$user, $args['type'], $game], $user);
            }
        }
        $redirect_url = $url;

        if(empty($url) || $this->handle_redirect_url){
            $mg      = $this->getNetworkModule($game);
            $network = strtolower($game['network']);

            if(empty($mg))
                return 'no-gp-network';

            /** @var Casino $nw */
            $nw   = phive('Casino');

            rgLimits()->resetTimeout($user);

            $is_mp = false;

            // Battle user id looks like this: 123e123 if the user has id 123 and the battle entry has id 123
            if(!empty($args['user_id'])){

                if($t_entry['user_id'] != uid()){
                    $this->logForFixedUsers('wrong tournament entry');
                    die('wrong tournament entry');
                }

                if(!phive('Tournament')->checkForCorrectGame($game, $t)){
                    $this->logForFixedUsers('wrong game');
                    die('Wrong game');
                }

                $_SESSION['token_uid'] = $args['user_id'];
                $is_mp = true;
            }

            if($args['type'] === 'mobile'){
                $url = $mg->getMobilePlayUrl($args['game_ref'], $args['lang'], phive('UserHandler')->getSiteUrl(), $game, $args, $show_demo);
            } else {
                $url = $mg->getDepUrl($args['game_id'], $args['lang'], null, $show_demo);
            }

            if(!$url) {
                $this->handle_redirect_url = true;
                $redirect_url = '/404/';
            }
            unset($_SESSION['token_uid']);

            if(!empty($user)){

                $nw->finishUniqueGameSession($nw->getGsess([
                    'user_id'     => $user->getId(),
                    'game_ref'    => $game['ext_game_name'],
                    'device_type' => $game['device_type_num'],
                    'balance'     => $user->getBalance() + phive('Bonuses')->getBalanceByUser($user)
                ], $user, false), $user->data);

                if(phive()->getSetting('lga_reality') === true){
                    $bmax_reached = $user->mGet('betmax-reached');
                    if(!empty($bmax_reached)){
                        $user->mDel('betmax-reached');
                        $user->mDel('lgalimit-msg');
                    }
                }

                if(empty($is_mp)){
                    $this->incPlayedTimes($game['ext_game_name']);
                    $this->incCache($game['ext_game_name']);
                }

                uEvent('startgame', '', $game['game_name'], $game['game_id']);
                phive('UserHandler')->earnedCashback(5, $game['game_id']);

                //phMset($this->uniqueGameSession($user, $game), 1, 3600);
                //phive()->dumpTbl('playgame', $game, $user->getId());
                if(phive()->moduleExists('Race'))
                    phive('Race')->initiateEntries();

                // INHOUSE-FRB
                if( // check if inhouse frb is globally enabled
                    phive()->getSetting('inhousefrb') === true &&
                    // check if the game of this GP support inhouse frb
                    in_array($network, phive()->getSetting('inhousefrb_network'))
                ) {
                    // check if we have a active bonus_entries and if frb_remaining > 0
                    $aBonusEntry = phive('Bonuses')->getBonusEntryBy($user->data['id'], $game['game_id'], $network);
                    if (!empty($aBonusEntry) && $aBonusEntry['frb_remaining'] > 0) {
                    $nw->wsInhouseFrb($user->getId(), cLang(), 'frb.start-msg.html',
                        array_merge($game, array('frb_remaining' => $aBonusEntry['frb_remaining'])));
                    }
                }
            }
            if(!empty($_SESSION['local_usr']))
                phive("Bonuses")->failByRequirements($_SESSION['local_usr'], $game);
        }

        if ($this->handle_redirect_url) {
            return [$url, $redirect_url];
        } else {
            return $url;
        }
    }

  /*
  function getNetworkSum($date, $field = 'bets'){
    $str          = "SELECT SUM($field) AS $field, network FROM users_daily_game_stats WHERE `date` = '$date' GROUP BY network";
    $res          = phive('SQL')->loadKeyValues($str, 'network', $field);
    $res['total'] = array_sum($res);
    return $res;
  }
   */

  function recalcNetworkStats($date){
    phive('SQL')->query("DELETE FROM network_stats WHERE `date` = '$date'");
    $this->calcNetworkStats($date);
  }

  function getNetworkFractions($date, $country){
    $sql_str = "SELECT SUM(bets) AS bets, network FROM users_daily_game_stats WHERE `date` = '$date' AND network != '' AND country = '$country' GROUP BY network";
    $res     = phive('SQL')->loadArray($sql_str, 'ASSOC', 'network');
    if(empty($res)){
      return array(
        'microgaming' => 0.3,
        'nyx' => 0.3,
        'netent' => 0.2,
        'playngo' => 0.15,
        'multislot' => 0.05,
      );
    }else{
      $btot    = phive()->sum2d($res, 'bets');
      $ret     = array();
      foreach($res as $n => $arr)
        $ret[$n] = $arr['bets'] / $btot;
      return $ret;
    }
  }

  //function getSystemGame($network){
  //  return phive('SQL')->loadAssoc("SELECT * FROM micro_games WHERE tag = 'system' AND 'network' = '$network'");
  //}

  function calcNetworkStats($date){
    $taxmap  = phive('Cashier')->getTaxMap();
    $uds_table = phive('UserHandler')->dailyTbl();
    foreach(phive('Currencer')->getAllCurrencies() as $iso => $arr){
      $sql_str = "SELECT SUM(rewards) AS rewards, SUM(paid_loyalty) AS paid_loyalty, country, currency
                  FROM $uds_table
                  WHERE currency = '$iso'
                  AND `date` = '$date'
                  GROUP BY country";
      $udss  = phive('SQL')->loadArray($sql_str, 'ASSOC', 'country');
      foreach($udss as $country => $uds){
        $tax_percent = $taxmap[$uds['country']];
        $sql_str     = "SELECT SUM(bets) AS bets, SUM(wins) AS wins, SUM(jp_contrib) AS jp_contrib, network
                        FROM users_daily_game_stats
                        WHERE country = '$country'
                        AND currency = '$iso'
                        AND `date` = '$date'
                        GROUP BY network";
        $ns          = phive('SQL')->loadArray($sql_str, 'ASSOC', 'network');
        if(empty($ns) && (!empty($uds['rewards']) || !empty($uds['paid_loyalty']))){
          $fractions = $this->getNetworkFractions($date, $country);
          foreach($fractions as $n => $f){
            $rewards = ($uds['rewards'] + $uds['paid_loyalty']) * $f;
            $ins = array(
              'network'     => $n,
              'rewards'     => $rewards,
              'tax'         => -$rewards * $tax_percent,
              'country'     => $country,
              'currency'    => $iso,
              'tax_percent' => $tax_percent,
              'date'        => $date
            );
            phive('SQL')->insertArray('network_stats', $ins);
          }
        }else{
          $btot        = phive()->sum2d($ns, 'bets');
          foreach($ns as $network => $udgs){
            $rewards = ($uds['rewards'] + $uds['paid_loyalty']) * ($udgs['bets'] / $btot);
            $gross   = $udgs['bets'] - $udgs['wins'] - $udgs['jp_contrib'];
            $ins     = array(
              'bets'        => $udgs['bets'],
              'wins'        => $udgs['wins'],
              'jp_contrib'  => $udgs['jp_contrib'],
              'gross'       => $gross,
              'network'     => $udgs['network'],
              'rewards'     => $rewards,
              'tax'         => ($gross - $rewards) * $tax_percent,
              'country'     => $country,
              'currency'    => $iso,
              'tax_percent' => $tax_percent,
              'date'        => $date
            );
            phive('SQL')->insertArray('network_stats', $ins);
          }
        }
      }
    }
  }

  function getGameMap(){
    return phive('SQL')->lb()->loadArray('SELECT * FROM micro_games', 'ASSOC', 'ext_game_name');
  }

    function calcGameUserStats($date, $db = ''){
        if(empty($db))
            $db = phive('SQL');
        $taxmap    = $db->loadKeyValues("SELECT * FROM bank_countries", 'iso', 'tax');
        $sql_str   = $this->getGameUserSql($date, 'bets', true);
        //$bets      = phive('SQL')->shs('merge', '', null, 'bets')->loadArray($sql_str, 'ASSOC', array('user_id', 'game_ref', 'device_type'));
        $bets      = $db->loadArray($sql_str, 'ASSOC', array('user_id', 'game_ref', 'device_type'));

        // For use as a PHP join on the game to get the network.
        $gmap      = $this->getGameMap();
        foreach($bets as &$b){
            unset($b['id']);
            if (empty($b['network'])) {
                $b['network'] = $gmap[$b['game_ref']]['network'];
            }
            $db->insertArray('users_daily_game_stats', $b);
        }

        $wins = $db->loadArray(
            $this->getGameUserSql($date, 'wins', false),
            'ASSOC',
            array('user_id', 'game_ref', 'device_type'));

        foreach($wins as &$w){
            if(empty($w['wins']))
                continue;
            $op_fee = round($w['op_fee']);
            unset($w['op_fee']);
            unset($w['id']);
            $w['network'] = $gmap[$w['game_ref']]['network'];

            $db->save('users_daily_game_stats', $w);
            if(!empty($op_fee)){
                $where = phive('SQL')->makeWhere($this->dailyUniqKey($w, false));
                $res = $db->query("UPDATE users_daily_game_stats SET op_fee = op_fee - $op_fee $where");
            }
        }

        //$frb_wins = phive('SQL')->shs('merge', '', null, 'wins')->loadArray($this->getGameUserSql($date, 'wins', false, true));
        $frb_wins = $db->loadArray($this->getGameUserSql($date, 'wins', false, true));
        foreach($frb_wins as $w){
            $w['frb_wins'] = $w['wins'];
            $w['network'] = $gmap[$w['game_ref']]['network'];
            unset($w['wins']);
            unset($w['op_fee']);
            unset($w['id']);
            $db->save('users_daily_game_stats', $w);
        }

        $this->fixNetwork($date, $db);
    }

    /**
     * Return, if exist, "users_daily_game_stats" for the current day
     * used to check if we need to insert/update daily realtime stats
     * @deprecated TODO remove this function
     *
     * @param $ud
     * @param $tr
     * @return mixed
     */
    function getUdgsRow(&$ud, &$tr){
        return $this->db->sh($ud, 'id')->loadAssoc('', 'users_daily_game_stats', [
            'user_id'     => $ud['id'],
            'date'        => phive()->today(),
            'game_ref'    => $tr['game_ref'],
            'device_type' => $tr['device_type']
        ], true);
    }

    /**
     * Insert minimum required info for "users_daily_game_stats", triggered on first bet/win of the player.
     * @deprecated TODO remove this function
     *
     * @param $ud
     * @param $tr
     * @param $game
     * @return mixed
     */
    function insertUdgsRow(&$ud, &$tr, &$game){
        $insert = [
            'user_id'     => $ud['id'],
            'username'    => $ud['username'],
            'firstname'   => $ud['firstname'],
            'lastname'    => $ud['lastname'],
            'date'        => phive()->today(),
            'game_ref'    => $tr['game_ref'],
            'currency'    => $ud['currency'],
            'device_type' => $tr['device_type'],
            'country'     => $ud['country']
        ];

        $insert['network']   = $game['network'];
        $new_id              = $this->db->sh($ud, 'id')->insertArray('users_daily_game_stats', $insert);
        return $this->db->sh($ud, 'id')->loadAssoc('', 'users_daily_game_stats', ['id' => $new_id], true);
    }

    /**
     * Update "users_daily_game_stats" on every bet.
     * "$bet" that is passed here already contains all the proper calculations
     * @deprecated TODO remove this function
     * @param $ud
     * @param $bet
     * @param $game
     */
    function udgsOnBet(&$ud, &$bet, &$game){
        $udgs = $this->getUdgsRow($ud, $bet);
        if(empty($udgs)){
            $udgs = $this->insertUdgsRow($ud, $bet, $game);
        }
        $udgs['bets']       += $bet['amount'];
        $udgs['op_fee']     += $bet['op_fee'];
        $udgs['jp_contrib'] += $bet['jp_contrib'];
        $udgs['bets_count']++;
        $this->db->sh($ud, 'id')->save('users_daily_game_stats', $udgs);
    }

    /**
     * @deprecated TODO remove this function
     * Update "users_daily_game_stats" on every win
     * "$win" that is passed here already contains all the proper calculations
     * "bonus_bet = 3" is a free spin so we don't have to remove the "op_fee"
     *
     * @param $ud
     * @param $win
     * @param $game
     */
    function udgsOnWin(&$ud, &$win, &$game){
        $udgs = $this->getUdgsRow($ud, $win);
        if(empty($udgs)){
            $udgs = $this->insertUdgsRow($ud, $win, $game);
        }
        $udgs['wins_count']++;
        if((int)$win['bonus_bet'] == 3){
            $udgs['frb_wins'] += $win['amount'];
        }else{
            $udgs['wins']   += $win['amount'];
            $udgs['op_fee'] -= $win['op_fee'];
        }
        $this->db->sh($ud, 'id')->save('users_daily_game_stats', $udgs);
    }

  function dailyUniqKey(&$arr, $as_str = true){
    if($as_str)
      return $arr['user_id'].$arr['date'].$arr['game_ref'].$arr['device_type'];
    return array('user_id' => $arr['user_id'], 'date' => $arr['date'], 'game_ref' => $arr['game_ref'], 'device_type' => $arr['device_type']);
  }

    function getNetworks($as_select = false){
        $res = phive('SQL')->readOnly()->lb()->load1DArr("SELECT DISTINCT network FROM micro_games WHERE network != '0' GROUP BY network", 'network');
        if(!$as_select)
            return $res;
        return array_combine($res, $res);
    }

  function getClasses(){
    $res = $this->getNetworks();
    $key = array_search('microgaming', $res);
    $res[$key] = 'quickfire';
    return $res;
  }

    /*
  function getCurWinners($limit = 10){
    $str = "SELECT us.*, mg.* FROM users_daily_game_stats us, micro_games mg WHERE us.game_ref = mg.ext_game_name ORDER BY us.date DESC, us.wins DESC LIMIT 0,$limit";
    return phive('SQL')->loadArray($str);
  }
    */

  function getDailyUserStat($date, $uid, $field){
    return phive('SQL')->getValue("SELECT $field FROM users_daily_stats WHERE `date` = '$date' AND user_id = $uid");
  }

  function getLocalJpBalance(){
    return phive('SQL')->getValue("SELECT SUM(jp_value) FROM micro_jps WHERE `local` = 1 AND currency = 'EUR'");
  }

  function getLocalJpBalances(){
    return phive('SQL')->loadArray("SELECT * FROM micro_jps WHERE `local` = 1 AND currency = 'EUR'");
  }
  /**
   *
   * TODO remove this function and its invocations as game attributes do not even exists anymore
   * Get all additional game attributes for game review box page.
   * The DB table is game_attributes that has a self hierarchical structure (parent_id)
   * The conditions are that:
   * if you are in the frontend some fields could be NOT visible (field: visible_front_end)
   * @param type $gId - Gameid
   * @param type $tab - Tab is a jquery tab element in the front end. From back office admin can choose where a field should go...
   * @param type $backoffice - If in backoffice or not
   * @param type $check_visible_game_review_page - If is setted the game review box is visible instead the play box
   * @return type
   */
  function getGameAdditionalAttributes($gId,$tab = false,$backoffice = false,$check_visible_game_review_page = false) {
      return false;
    $id = $gId ? intval($gId) : 0;
    $query_add_opt = "SELECT id, children_id, html_type, alias, label, MAX(value) AS val, possible_values, default_value, visible_front_end, tab_front_end,  priority
                    FROM(
                      SELECT parent.id AS id, parent.alias, parent.html_type AS html_type, parent.label, children.value, children.id AS children_id, parent.possible_values, parent.default_value, parent.visible_front_end, parent.tab_front_end,parent.priority
                      FROM
                        game_attributes parent
                      JOIN
                        game_attributes children
                      ON
                        parent.id = children.parent_id
                      AND
                        children.game_id = {$id}";
    if(!$backoffice){
      if($check_visible_game_review_page){
        $query_add_opt.= " AND parent.label = 'ENABLE GAME REVIEW PAGE'";
      }else{
        $query_add_opt.= " AND parent.visible_front_end = 1";
      }
    }
    $query_add_opt.= " UNION
                        SELECT id, alias, html_type, label,'' AS value, '' AS children_id, possible_values, default_value, visible_front_end, tab_front_end, priority
                       FROM game_attributes
                         WHERE parent_id = 0";
    if(!$backoffice){
      if($check_visible_game_review_page){
        $query_add_opt.= " AND label = 'ENABLE GAME REVIEW PAGE'";
      }else{
        $query_add_opt.= " AND visible_front_end = 1";
      }
    }
    $query_add_opt.= " )master";
    $query_add_opt.= " WHERE 1";
    if(!$backoffice){
      if($check_visible_game_review_page){
        $query_add_opt.= " AND label = 'ENABLE GAME REVIEW PAGE'";
      }else{
        $query_add_opt.= " AND visible_front_end = 1";
      }
    }
    if ($tab) {
      $query_add_opt.= " AND tab_front_end = '{$tab}'";
    }
    $query_add_opt.= " GROUP BY id";
    $query_add_opt.= " ORDER BY priority asc";

    $aResults = phive('SQL')->loadArray($query_add_opt);
    return $aResults;
  }

  function sumColsFromGameSessions($u_obj, $cols, $dates = []){
      $sums        = $this->db->makeSums($cols);
      $where_tspan = empty($dates) ? '' : $this->db->tRng($dates[0], $dates[1], 'start_time');
      return $this->db->sh($u_obj)->loadAssoc("SELECT $sums FROM users_game_sessions WHERE user_id = {$u_obj->getId()} $where_tspan");
  }

  function getLastLossFromGameSessions($u_obj, $dates = []){
      $last_loss_date = 'MAX(start_time) as last_loss';
      $where_tspan = empty($dates) ? '' : $this->db->tRng($dates[0], $dates[1], 'start_time');
      $where_loss_only = "AND result_amount < 0";
      return $this->db->sh($u_obj)->loadAssoc("SELECT $last_loss_date FROM users_game_sessions WHERE user_id = {$u_obj->getId()} $where_tspan $where_loss_only");
  }

  /**
   *
   * TODO this is a mess this needs to be done properly.
   *
  *     @param $game Array game data
  *     @return bool
  *     Checks if the game can run on an iframe, it should be enabled at all levels so we are able to switch them
  *     On/Off on a individual, network or global basis
  */
  public function gameInIframe($game = [])
  {
      try {

          if ($this->getSetting('mobile_games_in_iframe', true) || phive()->getSetting('enable_mobile_split_game', true)) {
              return true;
          }

          $user = cu();
          $jurisdiction = licJur($user);
          $networkModule = $this->getNetworkModule($game);

          if(!empty($networkModule)){
              if ($networkModule->getLicSetting('mobile_games_in_iframe', $user)) {
                  return true;
              }
          }

          // The license setting overrides anything else
          if (lic('forceMobileGamesInIframe') == 'Yes') {
              return true;
          }elseif (lic('forceMobileGamesInIframe') == 'No') {
              return false;
          }

          $global =  $this->getSetting('mobile_games_in_iframe') ?? false;// By global setting
          $game = $game['game_in_iframe'] ?? true; // By game setting

          return $global && $game;
      } catch (Exception $e) {
          error_log("Silent Fatal error: " . $e->getMessage());
          error_log(json_encode($game));
          return false;
      }
  }

    /**
     * @return array
     */
  public function getGamesOverridesJoin()
  {
        $jur = $this->getCountryForOverride();

        $sel_games_overrides = ', go.payout_extra_percent AS override_payout_extra_percent, IFNULL(go.payout_percent, mg.payout_percent) as override_payout_percent';
        $join_games_overrides = "LEFT JOIN game_country_overrides go ON (go.country IN('$jur', 'ALL') AND go.game_id = mg.id) ";

        return [$sel_games_overrides, $join_games_overrides];
  }

    /**
     * This extra condition is used on mobile only and is needed to avoid extra query to fetch the right game_url.
     *
     * most games have that column defined only on desktop version, on mobile is defined only if no desktop version exist.
     *
     * @param string $device_type
     * @return string[]
     */
    private function overrideGameUrlByDeviceType($device_type = 'flash')
    {
        $mobile_extra_select = '';
        $mobile_extra_join = '';
        if($device_type === 'html5') {
            $mobile_extra_select = "
                , IF(mg.game_url != '', mg.game_url, mg_desktop.game_url) AS game_url
                , IF(mg.id != '', mg.id, mg_desktop.id) AS id
                , IF(mg.ext_game_name != '', mg.ext_game_name, mg_desktop.ext_game_name) AS ext_game_name
                , mg_desktop.game_id as desktop_game_id
            ";
            $mobile_extra_join = "LEFT JOIN micro_games mg_desktop ON mg.id = mg_desktop.mobile_id";
        }
        return [$mobile_extra_select, $mobile_extra_join];
    }

    /**
     * @param $filter_name
     * @param string $device_type
     * @param null $subtag
     * @param null $where_extra
     * @param bool $should_join_jps
     * @param bool $gps_via_gpr
     * @return array|null
     */
    public function getTaggedByWrapper(
        $filter_name,
        $device_type = 'desktop',
        $subtag = null,
        $limit = false,
        $where_extra = null,
        $should_join_jps = false,
        $gps_via_gpr = false,
        $user_id = null,
        $favorite = false,
        $operator = ''
    ) {
        $offset = null;
        $length = null;
        // $limit is set to true only inside new mobile game search (so on normal pages we get back all the results)
        if(!empty($limit)) {
            $offset = 0;
            $length = 100;
        }
        $tag = 'all';
        $order_by = 'played_times_in_period DESC';
        $where_device = $device_type == 'desktop' ? "mg.device_type = 'flash'" : "mg.device_type = 'html5'";
        if(!empty($where_extra)) {
            $where_device .= ' AND '.$where_extra;
        }
        $period = '';

        switch($filter_name) {
            case $filter_name == 'subtag':
                $period = phive()->yesterday();
                break;
            case $filter_name == 'primary_tag':
                $period = phive()->yesterday();
                $tag = $subtag;
                $subtag = 'all';
                break;
            case $filter_name == 'subtag_footer' && strpos($subtag, 'cgames') !== false: // Ex. 'new.cgames' or 'featured.cgames'
                $order_by = 'gtcid DESC';
                break;
            case 'hot':
                $period = phive()->yesterday();
                break;
            case 'popular':
                $period = phive()->lastMonth();
                break;
            case 'last-played':
                $user_id = $user_id ? (int) $user_id : null;
                return $this->getLastPlayed($device_type == 'desktop' ? 'flash_last_played' : 'mobile_last_played', false, $where_extra,  $user_id);
            default:
                $tag = $filter_name; // This is the tag that is passed from the front end, like 'new', 'featured', 'popular', etc.
                // TODO return all games if nothing is passed??
                break;
        }

        return $this->getTaggedBy(
            $tag,
            $offset,
            $length,
            $subtag,
            $order_by,
            $where_device,
            $period,
            $should_join_jps,
            true,
            $operator,
            [],
            [],
            true,
            '',
            $gps_via_gpr,
            $user_id,
            $favorite
        );
    }

    /**
     * Extract the requested amount of boosted games, the available types are:
     * - boosted standard games
     * - boosted live casino games
     *
     * @param $games
     * @param int $weekend_booster_games_count
     * @param int $boosted_live_casino_games_count
     * @param int $rtp_games_count
     * @param float $rtp_value
     * @param null|string $rtp_category
     * @return array
     */
    public function extractBoostedGamesFromList($games, $weekend_booster_games_count = 0, $boosted_live_casino_games_count = 0, $rtp_games_count = 0, $rtp_value = 0, $rtp_category = null, $order = null) {
        if(empty($weekend_booster_games_count) && empty($boosted_live_casino_games_count) && empty($rtp_games_count)) {
            return [$games, [], []];
        }

        $weekend_booster_games = [];
        $boosted_live_casino_games = [];
        $rtp_games = [];
        $rtp_category = empty($rtp_category) || $rtp_category == 'all' ? null : explode(',', $rtp_category);

        // We remove from the $games array, using return false, the games that fall under one of the other categories.
        $games = array_filter($games, function($cur_game, $key) use (&$weekend_booster_games_count, &$boosted_live_casino_games_count, &$weekend_booster_games, &$boosted_live_casino_games, &$rtp_games_count, &$rtp_games, $rtp_value, $rtp_category) {
            return $this->filterGames($cur_game, $weekend_booster_games_count, $boosted_live_casino_games_count, $weekend_booster_games, $boosted_live_casino_games, $rtp_games_count, $rtp_games, $rtp_value, $rtp_category);
            }, ARRAY_FILTER_USE_BOTH);


        return [$games, $weekend_booster_games, $rtp_games, $boosted_live_casino_games];
    }


    /**
     * @param $cur_game
     * @param $weekend_booster_games_count
     * @param $boosted_live_casino_games_count
     * @param $weekend_booster_games
     * @param $boosted_live_casino_games
     * @param $rtp_games_count
     * @param $rtp_games
     * @param $rtp_value
     * @param $rtp_category
     * @return bool
     */
    public function filterGames(&$cur_game, &$weekend_booster_games_count, &$boosted_live_casino_games_count, &$weekend_booster_games, &$boosted_live_casino_games, &$rtp_games_count, &$rtp_games, $rtp_value, $rtp_category): bool
    {
        if (!is_null($cur_game['override_payout_extra_percent'])) {
            $cur_game['payout_extra_percent'] = $cur_game['override_payout_extra_percent'];
        }
        if ($boosted_live_casino_games_count > 0 && $cur_game['tag'] == 'live-casino') {
            $boosted_live_casino_games[] = $cur_game;
            $boosted_live_casino_games_count--;
            return false;
        } elseif ($weekend_booster_games_count > 0 && $cur_game['payout_extra_percent'] > 0) {
            $weekend_booster_games[] = $cur_game;
            $weekend_booster_games_count--;
            return false;
        } elseif ($rtp_games_count > 0 && !empty($rtp_value) && $cur_game['override_payout_percent'] <= round($rtp_value / 100, 4) && (empty($rtp_category) || in_array($cur_game['tag'], $rtp_category))) {
            $rtp_games[] = $cur_game;
            $rtp_games_count--;
            return false;
        } else {
            return true;
        }
    }

    /**
     * Add Multiplay flags to the game
     * - gp_allows_multiplay - if the GP support multiplay
     * - gp_needs_protected_focus - if the GP game needs some special autofocus on the game to avoid freezing
     * - prevent_multigame_load - if TRUE we need to prevent opening the game on the client side showing a popup with an error message.
     *
     * @param $game
     * @param $existing_games
     */
    public function addMultiplayGameFlags(&$game, $existing_games_network, $changing_game_index) {
        // This is the desktop setting for the "whitelisted" GPs that allow multiplay
        // but it's a REALLY OLD setting that is NOT FULLY RELIABLE so for now we only block specific providers inside "not_allowed_multi_game-gps"
//        $allowed_multiplay_gps = $this->getSetting('multi-play-gps');
        $not_allowed_multiplay_gps = $this->getSetting('not_allowed_multi_game-gps', ['playtech']);
        $protected_focus_mode_gps = $this->getSetting('protected_focus_mode-gps', ['playngo']);

        // if true will trigger a popup when opening the game.
        $prevent_multigame_load = false;
        $gp_allows_multiplay = true;
        $gp_needs_protected_focus = false;
        $game_network = $game['network'];

        // we don't have any game loaded yet.
        if(!empty($existing_games_network)) {
            // if the provider doesn't allows multiplay
            if(in_array($game_network, $not_allowed_multiplay_gps)) {
                $gp_allows_multiplay = false;
            }
            // if the provider games needs to run under "protected focus" mode
            if(in_array($game_network, $protected_focus_mode_gps)) {
                $gp_needs_protected_focus = true;
            }

            // we already have 1 game open of the same network AND one of the other flag exist we prevent the game from being loaded.
            // but only if we are adding a new game not replacing the existing one
            if(in_array($game_network, $existing_games_network) && $existing_games_network[$changing_game_index] != $game_network && (!$gp_allows_multiplay || $gp_needs_protected_focus)) {
                // we cannot have more than 1 game that require special feature at the same time
                $prevent_multigame_load = true;
            }
        }

        $game['prevent_multigame_load'] = $prevent_multigame_load;
        $game['gp_allows_multiplay'] = $gp_allows_multiplay;
        $game['gp_needs_protected_focus'] = $gp_needs_protected_focus;
    }




    /**
     * Get Filtered games and add flags appropriately
     *
     * @param array $games_filter_list Game filter List 'search game filter'
     * @param array $current_games Currently games that are being played
     * @return Array of games with adeded flags
     *
     */
    public function addMultiplayGameFlagsToGameList(&$games_filter_list, $current_games = [], $changing_game_index = null)
    {
        if(empty($current_games)) {
            $current_games = $_GET['currentGames']; // Set on desktop multigame mode search
        }
        if(empty($current_games)) {
            $current_games = [];
        }

        // if not index is specified then we fallback to 0 (index of the first game)
        if(empty($changing_game_index)) {
            $changing_game_index = $_GET['currentGameIndex'] ?: 0;
        }

        $existing_game_networks = array_map(function($game){ return $game['network'];}, $current_games);

        // get game network from the list
        foreach ($games_filter_list as $key => $game){
            $this->addMultiplayGameFlags($games_filter_list[$key], $existing_game_networks, $changing_game_index);
        }
    }


    /**
     *
     * Gets the game override by game id
     *
     * The game is checked if it is overridden, if overridden the override game is returned,
     * if not the original game is returned.
     *
     * @param string $gid game_id
     * @param DBUser|null $user
     * @param int $device 0 or 1
     * @return array game
     *
     */
    public function getGameOrOverrideByGid($gid, $user = null, $device = 0){
        $game = $this->getByGameId($gid, $device, $user);
        $game = $this->overrideGame($user, $game);
        return $game;
    }


    /**
     *
     * Gets the game override by game ref
     *
     * The game is checked if it is overridden, if overridden the override game is returned,
     * if not the original game is returned.
     *
     * @param string $gref ext_game_name
     * @param DBUser|null $user
     * @param int $device 0 or 1
     * @return array game
     *
     */
    public function getGameOrOverrideByGref($gref, $user = null, $device = null){
        $game = $this->getByGameRef($gref, $device, $user);
        $game = $this->overrideGame($user, $game);
        return $game;
    }

    /**
     * Logs slow game replies above certain threshold that is considered to cause noticeable lag for the player during the spin
     *
     * @param int $duration of the request in seconds
     * @param array $insert information to store in logs
     */
    public function logSlowGameReply($duration, $insert)
    {
        if ($duration < $this->getSetting('slow_game_replies_threshold', 1.5)) {
            return;
        }

        if(!empty($GLOBALS['t_eid'])) {
            $insert['is_bos'] = 1;
        }

        phive()->fire('casino', 'CasinoSlowReplyEvent', [$insert], 0, function () use ($insert) {
            $setting = phive()->getSetting('slow_game_replies_logger', 'db');
            if($setting === 'db' || $setting === 'both') {
                $this->db->insertArray('slow_game_replies', $insert);
            }
            if($setting === 'file' || $setting === 'both'){
                phive('Logger')->getLogger('slow_game_replies')->log(json_encode($insert));
            }
        });
    }

    /**
     * Get where and join condition for expanded game categories search.
     *
     * @param string $expanded_game_category
     * @return string[]
     */
    public function getWhereConditionByExpandedGameCategory($expanded_game_category = '')
    {
        $expanded_game_categories = $this->getSetting('expanded_game_categories', []);
        if (empty($expanded_game_category) || !array_key_exists($expanded_game_category, $expanded_game_categories)) {
            return ['', ''];
        }
        $rules = $expanded_game_categories[$expanded_game_category];
        $join = '';
        $where_direct = '';
        $where_combined = '';
        if (!empty($rules['direct_match'])) {
            $tags = $this->db->makeIn($rules['direct_match']);
            $where_direct = "mg.tag IN ({$tags})";
        }
        if (!empty($rules['combined_match'])) {
            $main_tags = $this->db->makeIn($rules['combined_match']['main_tags']);
            $sub_tags = $this->db->makeIn($rules['combined_match']['sub_tags']);
            $where_combined = "(mg.tag IN ({$main_tags}) AND game_tags.alias IN ({$sub_tags}))";
            $join .= "
                LEFT JOIN game_tag_con ON mg.id = game_tag_con.game_id
                LEFT JOIN game_tags ON game_tags.id = game_tag_con.tag_id
            ";
        }
        if (!empty($where_direct) && !empty($where_combined)) {
            $where = "($where_direct OR $where_combined)";
        } else {
            $where = $where_direct . $where_combined; // being one empty we don't care of joining them with OR
        }

        return [$where, $join];
    }

    /**
     * Return the game_refs (ext_game_name) available for requested game_category and device type.
     *
     * @param $game_category - expanded game category
     * @param $device_type_num - 0/1 desktop/mobile
     * @return array
     */
    public function getGameRefsFilteredByExpandedCategory($game_category, $device_type_num = 0)
    {
        list($where_category, $join_category) = phive('MicroGames')->getWhereConditionByExpandedGameCategory($game_category);
        $where_category = !empty($where_category) ? ' AND '.$where_category : '';
        $sql_games_by_category = "
                SELECT mg.ext_game_name
                FROM micro_games mg
                {$join_category}
                WHERE mg.device_type_num = {$device_type_num}
                {$where_category}
            ";
        return phive('SQL')->loadCol($sql_games_by_category, 'ext_game_name');
    }

    /**
     * Return the expanded game category matching the provided tag/subtag combination.
     * Ex. ('blackjack', []) => 'blackjack'
     *     ('live-casino', ['liveblackjack.cgames']) => 'blackjack'
     *     ('live-casino', ['_roulette-french.cgames']) => 'roulette'
     *     ('videoslots', ['any_bonus_game_tag']) => 'slots'
     *
     * @param string $main_tag
     * @param array $sub_tags
     * @return int|mixed|string
     */
    public function getExpandedGameCategoryByTagAndSubtag(string $main_tag, array $sub_tags = [])
    {
        if (empty($main_tag) && empty($sub_tags)) {
            return 'slots';
        }

        $expanded_game_categories = $this->getSetting('expanded_game_categories', []);
        foreach ($expanded_game_categories as $expanded_game_category => $details) {
            if (in_array($main_tag, $details['direct_match'], true)) {
                return $expanded_game_category;
            }
            if (in_array($main_tag, $details['combined_match']['main_tags'], true)) {
                foreach ($sub_tags as $sub_tag) {
                    if (in_array($sub_tag, $details['combined_match']['sub_tags'], true)) {
                        return $expanded_game_category;
                    }
                }
            }
        }

        // TODO TBC if we can default to slots, being the most generic category (if yes remove initial IF condition)
        return 'slots';
    }

    public function addWhereProvinceClousure($prefix = "") {
        if(licSetting('require_main_province')) {
            $user = cu();
            $province =  $user ? $user->getProvince() : licSetting('default_main_province');
            if (!empty($province)) {
                return $prefix."blocked_provinces NOT LIKE '%" . $province . "%'";
            }
        }
        return "";
    }

    /**
     * Moves all games of a supplier / operator from one network to another network.
     *
     * NOTE: this method only works for networks where the game_id and ext_game_name columns are exactly the same.
     *
     * @param $operator string The operator to work with, ex: Push Gaming
     * @param $network string The network to move to, ex: pushgaming
     * @param $map array An array with the game_id and ext_game_name, including prefixes, where the id to move from is the key
     * and the id to move to is the value.
     * @param $flip_map bool True if we want to revert a move, ie if we for example moved from relax to push gaming and we have to revert back
     * to relax then we call this method again with this argument set to true.
     *
     * @return null
     */
    function moveOperatorGames($operator, $network, $map, $flip_map = false, $do_bonuses = false){
        $sql = phive('SQL');
        if($flip_map){
            $map = array_flip($map);
        }
        $games = phive('SQL')->loadArray("SELECT * FROM micro_games WHERE operator = '$operator'");
        foreach($games as $g){
            $old_gid  = $g['game_id'];
            $new_game = $g;

            if(empty($map[$g['ext_game_name']])){
                continue;
            }

            $new_gid                   = $map[$g['ext_game_name']];
            $new_game['game_id']       = $new_gid;
            $new_game['ext_game_name'] = $new_gid;
            $new_game['network']       = $network;
            $sql->save('micro_games', $new_game);

            if($do_bonuses){
                $sql->updateArray('bonus_types', ['game_id' => $new_gid, 'bonus_tag' => $network], ['game_id' => $old_gid]);
            }

            $base_path = phive('Filer')->getSetting('UPLOAD_PATH');
            $sql->updateArray('localized_strings', ['alias' => "gameinfo.$new_gid.html"], ['alias' => "gameinfo.$old_gid.html"]);
            $sql->updateArray('localized_strings', ['alias' => "gameinfo.$new_gid.header"], ['alias' => "gameinfo.$old_gid.header"]);
            rename($base_path . "/screenshots/{$old_gid}_big.jpg", $base_path . "/screenshots/{$new_gid}_big.jpg");
            rename($base_path . "/thumbs/{$old_gid}_c.jpg", $base_path . "/thumbs/{$new_gid}_c.jpg");
            $sql->shs()->query("UPDATE trophies SET game_ref = '$new_gid' WHERE game_ref = '$old_gid'");
            $sql->shs()->query("UPDATE trophy_events SET game_ref = '$new_gid' WHERE game_ref = '$old_gid'");
        }
    }

    /**
     *   Insert network libraries before the other scripts are loaded.
     *   Needed here since mobile and desktop games are loaded differently.
     *   @param string $networkName
     */
    public function addNetworkJsLibraries($networkName = '') {
        $module = phive($networkName);
        if (method_exists($module, 'getNetworkLibraries')) {
            $libraries = $module->getNetworkLibraries();
            $isNyx = $networkName == "Nyx" ?? true;
            foreach ($libraries as $lib) {
                if($isNyx && phive()->getCurrentDeviceNum() == 1){
                    echo "<script type=\"text/javascript\" src=\"{$lib}\" token=\"{$_SESSION['token']}\"></script>";
                }
                else{
                    echo "<script type=\"text/javascript\" src=\"{$lib}\"></script>";
                }

            }
        }
    }

    /**
     * Retrieves the list of last played game IDs from memory or cookies.
     *
     * This method checks whether the user is on a mobile app or not. For mobile app,
     * it retrieves the last played games from a redis.
     * Otherwise, it retrieves the data from cookies.
     *
     * @param string $key The key used to store/retrieve the list of last played game IDs.
     * @param int|null $user_id The ID of the user whose last played games are being retrieved.
     *
     * @return array An array of game IDs that represent the last played games.
     */
    private function getLastPlayedGamesFromMemory(string $key, ?int $user_id = null): array
    {
        if (phive()->isMobileApp()) {
            // Mobile App - retrieve from shard memory
            $last_played_game_ids = phMgetShard($key, $user_id);
        } else {
            // Website - retrieve from cookies
            $last_played_game_ids = !empty($_COOKIE[$key]) ? $_COOKIE[$key] : '';
        }

        return !empty($last_played_game_ids) ? explode(',', $last_played_game_ids) : [];
    }

    /**
     * Updates the list of last played game IDs in memory or cookies.
     *
     * This method stores the provided list of game IDs (as a comma-separated string)
     * either in a memory store (shard) for mobile app or in a cookie for website.
     *
     * @param string $key The key used to store the list of last played game IDs.
     * @param array $ids_array An array of game IDs representing the last played games.
     * @param int|null $user_id The ID of the user whose last played games are being updated (optional for desktop).
     *
     * @return void
     */
    private function updateLastPlayedGamesInMemory(string $key, array $ids_array, int $user_id = null): void
    {
        $imploded_ids = implode(',', $ids_array);
        $expire = time() + 60 * 60 * 24 * 300;

        if (phive()->isMobile()) {
            // Update shard memory for mobile app
            phMsetShard($key, $imploded_ids, $user_id, $expire);
        } else {
            // Update cookie for desktop
            setCookieSecure($key, $imploded_ids, $expire);
        }
    }

    /**
     * Updates the list of last played games for a given user by adding the specified game
     * ID at the beginning of the list. The list is then stored either in memory or cookies.
     *
     * This method ensures that the most recently played game appears at the start of the list.
     *
     * @param int $gameId The ID of the game to be added to the list of last played games.
     * @param int $user_id The ID of the user whose last played games are being updated.
     * @param string $key The key used to store/retrieve the list of last played games. Default is 'mobile_last_played'.
     *
     * @return void
     */
    function updateLastPlayed(int $gameId, int $user_id, string $key = 'mobile_last_played'): void
    {
        $last_played_game_ids = $this->getLastPlayedGamesFromMemory($key, $user_id);

        // If the list is empty, initialize it with the current game
        if (empty($last_played_game_ids)) {
            $last_played_game_ids = [$gameId];
        } else {
            // Otherwise, add the current game at the beginning of the list
            array_unshift($last_played_game_ids, $gameId);
        }

        $this->updateLastPlayedGamesInMemory($key, $last_played_game_ids, $user_id);
    }

    /**
     * @param $id
     * @param int $device
     * @param null $u_info
     * @param bool $should_join_jp
     * @return array|mixed
     */
    function getByGameIdByDevice($id, int $device = 0, $u_info = null, bool $should_join_jp = false)
    {
        return $device == 0
            ? $this->getByGameId($id, $device, $u_info, $should_join_jp)
            : $this->getByGameIdMobile($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    function getByGameIdMobile($id)
    {
        $sql = "SELECT mg.*
                FROM {$this->table()} AS mg
                JOIN {$this->table()} AS mg1 ON mg.id = mg1.mobile_id
                WHERE mg1.game_id = '{$id}'
                  AND mg1.active = 1
                  AND mg1.mobile_id IS NOT NULL";

        return phive('SQL')->lb()->loadAssoc($sql);
    }

    /**
     * Temporary logs to get information on why bos for mobile does not start for the following users
     *
     * @param string $name
     * @param array $data
     * @return void
     */
    public function logForFixedUsers(string $name, array $data = [])
    {
        $user = cu();
        $users_with_error = [375586,636049,612844];

        if($user instanceof DBUser and in_array($user->getId(), $users_with_error)) {
            phive('Logger')->getLogger('bos_logs')->info($name, $data);
        }
    }
}
