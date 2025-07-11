<?php

use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlags\InstadebitFraudFlag;
use Videoslots\FraudDetection\RevokeEvent;
use Videoslots\HistoryMessages\InterventionHistoryMessage;
use Goutte\Client as GoutteClient;
use Videoslots\FraudDetection\FraudFlagRegistry;

require_once __DIR__.'/Mts.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// TODO henrik create migration to remove fraud_rules and fraud_groups
// TODO henrik remove all logic related with the above tables.
// TODO henrik remove all admin2 logic related to the above tables.
class Fraud{

    function __construct($debug = false){
        $this->db = phive('SQL');
        $this->debug = $debug;
    }

    function doDebug(){
        $this->debug = true;
        return $this;
    }

    function mailFraudSection($subject, $content){
        $mh = phive('MailHandler');
        $mh->saveRawMail($subject, $content, '', $mh->getSetting('fraud_mail'));
    }

    public function removeCardFraudFlag($user){
        $user->deleteSetting('ccard-fraud-flag');
        //We tell the MTS to approve all the user's cards to prevent further flagging until he starts using a new card again
        // TODO: if we need to approve all cards here, we need to call the Dmapi for that, not the MTS
    }

    //TODO as list is getting bigger try to do fex deletes in one
    public function clearFraudFlags($user)
    {
        $this->removeCardFraudFlag($user);
        $user->deleteSetting('manual-fraud-flag');
        $user->deleteSetting('bonus-fraud-flag');
        $user->deleteSetting('bonus_abuse-fraud-flag');
        $user->deleteSetting('majority-fraud-flag');
        $user->deleteSetting('multi_deposit-fraud-flag');
        $user->deleteSetting('majority_sng_battles-fraud-flag');
        $user->deleteSetting('majority_unfinished_battles-fraud-flag');
        $user->deleteSetting('nodeposit-fraud-flag');
        $user->deleteSetting('suspicious-email-fraud-flag');

        $fraudFlagRegistry = new FraudFlagRegistry();
        $fraudFlagRegistry->revoke($user, RevokeEvent::ON_WITHDRAWAL_SUCCESS);

        $user->triggerStatusChange();
    }

    //TODO as list is getting bigger try to do only one query
    public function hasFraudFlag(&$u){
        foreach($this->getFraudTypes() as $type){
            if($u->hasSetting("$type-fraud-flag"))
                return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getFraudTypes()
    {
        $fraudFlagRegistry = new FraudFlagRegistry();
        return array_merge([
            'iban_country_mismatch',
            'ip_country_mismatch',
            'trustly_country',
            'instadebit',
            'instadebit-withdrawal',
            'interac-withdrawal',
            'majority_sng_battles',
            'majority_unfinished_battles',
            'manual_adjustment',
            'manual',
            'multi_deposit',
            'ccard',
            'bonus',
            'bonus_abuse',
            'loww',
            'nodeposit',
            'majority',
            'withdraw_last_24_hours_limit',
            'withdraw_limit',
            'source_of_funds_requested',
            'liability',
            'too_many_rollbacks',
            'total-withdrawal-amount-limit-reached',
            'suspicious-email',
            'negative-balance-since-deposit',
        ], array_map(function ($name) {
            return preg_replace('/-fraud-flag$/', '', $name);
        }, $fraudFlagRegistry->names()));
    }

    public function getFlags($u){
        $ret = [];
        foreach($this->getFraudTypes() as $type){
            $key = "$type-fraud-flag";
            if($u->hasSetting($key)){
                $ret[$key] = "$type-fraud-line";
            }
        }

        return $ret;
    }

    public function checkAdjustmentFlag($withdrawal, $actor)
    {
        $user = cu($withdrawal['user_id']);

        if (!$user->hasSetting('manual_adjustment-fraud-flag')) {
            return true;
        }

        $query = "SELECT count(*)
                    FROM ip_log
                    WHERE actor = {$actor} AND target = {$withdrawal['user_id']} AND tag = 'cash_transactions'
                      AND created_at >= '". $user->getSetting('manual_adjustment-fraud-flag') ."'";

        if (phive('SQL')->sh($withdrawal['user_id'], '', 'ip_log')->getValue($query) > 0) {
            return false;
        }

        return true;
    }

    public function checkTrustlyCountryFlag($user_id, $clearinghouse)
    {
        $clearinghouse = strtoupper(implode(' ', explode('_', $clearinghouse)));

        $user = cu($user_id);
        phive()->dumpTbl('trustly-account', [$user->data['country'], $clearinghouse], $user);

        return false; //TODO parse the IBAN

        if (empty($user) || $clearinghouse == 'IBAN') { //We ignore IBAN for now
            return;
        }
        $bc = phive("UserHandler")->userBankCountry($user);

        if (strtoupper($bc['name']) != $clearinghouse) {
            $user->setSetting('trustly_country-fraud-flag', 1);
        }
    }

  /**
   * @param $user
   * @param bool $do_sub_method
   * @return mixed
   */
    public function getMultiDepositsData($user, $data, $do_sub_method = false, $last_withdrawal_stamp)
    {
        $config_days = phive('Config')->getValue('out-limits', 'withdrawal-multi-deposits-flag-days');
        $res['days'] = $days = (int)(!empty($config_days) ? $config_days : 7);
        $res['result'] = false;
        $where_extra = " AND status != 'disapproved' AND card_hash like '____ __** **** ____'";
        $grouped_deposits = phive('CasinoCashier')->groupDeposits($user->getId(), 'card_hash', $last_withdrawal_stamp, '', $where_extra);

        if (count($grouped_deposits) > 1) {
          $res['result'] = true;
          $res['methods'] = array_keys($grouped_deposits);
        } else {
            $current_source = $user->getSetting('majority_source_current');

            //These flags are getting deleted by flagMajorityDeposits()
            /*  $current_majority = $user->getSetting('majority_flag_current');
            $current_sub_majority = $user->getSetting('majority_sub_flag_current');

            //First check old flags
            if (!empty($current_majority) || !empty($current_sub_majority)) {
                foreach ($grouped_deposits as $k => $v) {
                    if ($current_majority != $k) {
                        $res['result'] = true;
                        $res['methods'] = [$k];
                        break;
                    }
                    if ($do_sub_method === true && !empty($current_sub_majority)) {
                        if ($v['scheme'] != $current_sub_majority && $v['card_hash'] != $current_sub_majority) {
                            $res['result'] = true;
                            $res['methods'] = ["$k {$v['scheme']} {$v['card_hash']}"];
                            break;
                        }
                    }
                }
            }*/

            if (!empty($current_source)) {
                foreach ($grouped_deposits as $k => $v) {
                    if ($current_source != $k || $v['scheme'] != $current_source && $v['card_hash'] != $current_source) {
                        $res['result'] = true;
                        if ($current_source != $k) {
                            $res['methods'] = [$k];
                        } else {
                            $res['methods'] = ["$k {$v['scheme']} {$v['card_hash']}"];
                        }
                        break;
                    }
                }
            }
        }

        return $res;
    }

    public function getLiabilityUnallocatedAmounts($user)
    {
        $start_time = microtime(true);
        $user_id = $user->getId();
        $previous_month = array_map('intval', explode('-',date('Y-m', strtotime("-1 month"))));
        $previous = [];

        //Check if the user has an active liability prevent flag
        $liability_flag_date = $user->getSetting('liability-flag-prevent');

        //Load info to know when was the last report calculation finished, in case that info is not there, use fallback query
        $reports = $this->db->loadKeyValues("SELECT * FROM misc_cache WHERE id_str LIKE 'reports-%'", 'id_str', 'cache_value');

        //We check if liability report for previous month is ready and not doing a recalculation
        if (empty($reports['reports-last-users_monthly_liability'])) {
            $get_pre_liability = !empty($this->db->shs('merge', '', null, 'users_monthly_liability')
                ->getValue("SELECT count(*) as c
                            FROM users_monthly_liability
                            WHERE source = 0 AND year = {$previous_month[0]} AND month = {$previous_month[1]}")) ? true : false;
        } else {
            $get_pre_liability = $reports['reports-last-users_monthly_liability'] == date('Y-m', strtotime("-1 month")) ? true : false;
        }

        if (!empty($liability_flag_date) && $liability_flag_date >= date('Y-m-01')) {
            $get_pre_liability = false;
        }

        //if liability is available and not processing previous month then I get previous month data from there
        if ($get_pre_liability === true && (empty($reports['reports-processing-users_monthly_liability']) || $reports['reports-processing-users_monthly_liability'] < date('Y-m', strtotime("-1 month")))) {
            $previous = $this->getLiabilityData($user_id, $previous_month[0], $previous_month[1]);
            $start_date = date('Y-m-01');
        } else {
            $start_date = date('Y-m-01', strtotime("-1 month"));
        }

        if (!empty($liability_flag_date) && $liability_flag_date > $start_date) {
            $start_date = $liability_flag_date;
        }

        //I get the date when last uds day was generated
        if (empty($reports['reports-last-users_daily_stats'])) {
            $last_uds = $this->db->sh($user_id, null, 'users_daily_stats')->getValue("SELECT MAX(date) FROM users_daily_stats");
        } else {
            $last_uds = $reports['reports-last-users_daily_stats'];
        }

        //I check if uds is in progress, if it is affected just for simplicity I'm not using it
        if (!empty($reports['reports-processing-users_daily_stats']) && $reports['reports-processing-users_daily_stats'] >= $start_date) {
            $uds_where = " AND 1 = 0";
            $all_date = $start_date;
        } else {//Not in progress or is old
            $uds_where = " AND date BETWEEN '{$start_date}' AND '{$last_uds}'";
            $all_date = date('Y-m-d', strtotime("+1 day", strtotime($last_uds)));
        }

        $query_str = "SELECT
                    sum(net) AS net,
                    sum(opening) AS opening,
                    sum(closing) AS closing
                  FROM ( SELECT
                            sum(sub.sum)  AS net,
                            0 AS opening,
                            0 AS closing
                          FROM
                            (SELECT IFNULL(sum(bets) * -1, 0) + IFNULL(sum(wins),0) + IFNULL(sum(frb_wins),0) AS sum
                              FROM users_daily_stats
                              WHERE user_id = $user_id $uds_where
                              UNION ALL
                              SELECT IFNULL(sum(amount), 0) * -1 AS sum
                              FROM bets
                              WHERE user_id = $user_id
                                    AND created_at >= '{$all_date} 00:00:00'
                              UNION ALL
                              SELECT IFNULL(sum(amount), 0) AS sum
                              FROM wins
                              WHERE user_id = $user_id AND award_type != 4
                                    AND created_at >= '{$all_date} 00:00:00'
                              UNION ALL
                              SELECT
                                IFNULL(sum(amount),0) AS sum
                              FROM cash_transactions ct
                              WHERE user_id = $user_id AND ct.timestamp >= '{$start_date} 00:00:00'
                                    AND (
                                      ct.transactiontype IN (105, 104, 103, 100, 101, 1, 2, 3, 7, 8, 9, 12, 13, 29, 42, 43, 50, 60, 31, 32, 66, 67, 69, 72, 73, 76, 77, 78, 79, 80, 81, 84, 85, 86, 90, 34, 38, 52, 54, 61, 63, 64, 65, 91, 92)
                                      OR (ct.transactiontype IN (15, 53) AND description NOT LIKE '%-cancelled%' AND description NOT LIKE '%Super blocked so did not payout%')
                                      OR (ct.transactiontype = 14 AND description NOT LIKE '%-aid-%'))
                              UNION ALL
                              SELECT IFNULL(sum(amount),0) AS sum
                              FROM wins
                              WHERE user_id = $user_id AND award_type = 4
                                    AND created_at >= '{$start_date} 00:00:00'
                              UNION ALL
                              SELECT IFNULL(sum(bets) * -1, 0) + IFNULL(sum(wins),0) + IFNULL(sum(void),0) AS sum
                              FROM users_daily_stats_sports
                              WHERE user_id = $user_id $uds_where
                              UNION ALL
                              SELECT IFNULL(sum(amount), 0) * -1 AS sum
                              FROM sport_transactions
                              WHERE user_id = $user_id
                                    AND bet_type = 'bet'
                                    AND created_at >= '{$all_date} 00:00:00'
                              UNION ALL
                              SELECT IFNULL(sum(amount), 0) AS sum
                              FROM sport_transactions
                              WHERE user_id = $user_id
                                    AND bet_type = 'win'
                                    AND created_at >= '{$all_date} 00:00:00'
                              UNION ALL
                              SELECT IFNULL(sum(amount), 0) AS sum
                              FROM sport_transactions
                              WHERE user_id = $user_id
                                    AND bet_type = 'void'
                                    AND created_at >= '{$all_date} 00:00:00'
                            ) AS sub
                        UNION ALL
                          SELECT
                            0 AS net,
                            IFNULL(sum(cash_balance) + sum(bonus_balance),0) AS opening,
                            0 AS closing
                          FROM users_daily_balance_stats
                            WHERE user_id = $user_id AND date = '{$start_date}' AND source = 0
                        UNION ALL
                          SELECT
                            0 AS net,
                            0 AS opening,
                            (cash_balance + (SELECT IFNULL(SUM(balance),0) AS bal FROM bonus_entries WHERE user_id = $user_id AND status  = 'active')) AS closing
                          FROM users
                            WHERE id = $user_id
                   ) as sub_main";


        $current = phive('SQL')->readOnly()->sh($user_id)->loadArray($query_str)[0];
        $current['diff'] = $current['opening'] + $current['net'] - $current['closing'];

        $end_time = microtime(true) - $start_time;

        if ($end_time > 5) {
            phive()->dumpTbl('slow-liability-query-log', $end_time, $user_id);
        }

        $ignore_previous_month = phive()->getMiscCache('liability-report-adjusted-month') == date('Y-m', strtotime("-1 month"));

        if ($ignore_previous_month || empty($previous) || $previous['diff'] == 0) {
            $current['since'] = $start_date;
            return $current;
        } else {
            return [
                'opening' => $previous['opening'],
                'net' => $previous['net'] + $current['net'],
                'closing' => $current['closing'],
                'diff' => $previous['diff'] + $current['diff'],
                'since' => date('Y-m-01', strtotime("-1 month")),
                'previous-diff' => $previous['diff'],
                'current-diff' => $current['diff']
            ];
        }
    }

    public function getLiabilityData($user_id, $year, $month)
    {
        $replica_db = $this->db;
        $res['opening'] = $replica_db->sh($user_id, null, 'users')
            ->getValue("SELECT IFNULL(sum(cash_balance) + sum(bonus_balance),0) FROM users_daily_balance_stats WHERE source = 0 AND user_id = $user_id AND date = '{$year}-{$month}-01'");
        $res['closing'] = $replica_db->sh($user_id, null, 'users')
            ->getValue("SELECT IFNULL(sum(cash_balance) + sum(bonus_balance),0) FROM users_daily_balance_stats WHERE source = 0 AND user_id = $user_id AND date = '". date('Y-m-d', strtotime("+1 month", strtotime("{$year}-{$month}-01"))) ."'");
        $res['net'] = $replica_db->sh($user_id, null, 'users')
            ->getValue("SELECT IFNULL(sum(amount),0) as res FROM users_monthly_liability WHERE user_id = $user_id AND year = {$year} AND month = {$month} AND source = 0");
        $res['diff'] = $res['opening'] + $res['net'] - $res['closing'];

        return $res;
    }

    public function sendLiabilityNotification($user_obj, $liability)
    {
        try {
            /** @var MailHandler2 $mh */
            $mh = phive('MailHandler');
            if (phive()->getSetting('liability_notification', false) == false) {
                return;
            }
            $domain = phive()->getSetting('domain');
            $user = $user_obj->data;
            if ($user_obj->hasSetting('liability-fraud-flag')) {
                $subject = "[Priority] Account liability review | Flagged user: {$user['id']} | {$domain}";
            } else {
                //If not flagged and difference is from a previous closed month, no notification then
                if (empty($liability['current-diff']) && !empty($liability['previous-diff'])) {
                    return;
                }
                $subject = "Account liability review | Not flagged user: {$user['id']} | {$domain}";
            }
            if (!isset($liability['previous-diff']) || $liability['diff'] == $liability['previous-diff']) {
                $month = date('F', strtotime("-1 month"));
            } elseif ($liability['diff'] == $liability['current-diff']) {
                $month = date('F');
            } else {
                $month = date('F', strtotime("-1 month")) .' and '. date('F');
            }
            $content = "<p>Account review needed related to liability check</p>";
            $content .= "<table><tr><td>{$user['username']}</td><td>{$user['id']}</td><td>{$liability['diff']}</td><td>{$month}</td></tr></table>";
            $content .= "<p>Diff: {$liability['diff']} since {$liability['since']}</p>";
            $to = $mh->getSetting("dev_support_mail", 'devsupport@videoslots.com');
            $mh->saveRawMail($subject, $content, $mh->getNotificationAddress(), $to);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function getWithdrawalDepositSum($user_id, $days = 30)
    {
        $replica_db = $this->db;
        $res['deposits'] = $replica_db->sh($user_id, null, 'deposits')
          ->getValue("SELECT IFNULL(SUM(amount), 0) as sum FROM deposits WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$days} DAY AND status != 'disapproved' AND user_id = {$user_id}");
        $res['withdrawals'] = $replica_db->sh($user_id, null, 'pending_withdrawals')
          ->getValue("SELECT IFNULL(SUM(amount), 0) as sum FROM pending_withdrawals WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$days} DAY AND status != 'disapproved' AND user_id = {$user_id}");

        return $res;
    }

    /**
     * Returns true for anyone who has played a sng battle where majority of users did not finish their spins
     * @param DBUser $user
     * @param $last_withdrawal_stamp
     * @return bool I don't return the data as for now they didn't ask for a list of tournaments
     */
    public function getUnfinishedTournamentsFlagData($user, $last_withdrawal_stamp)
    {
        $start_time = microtime(true);

        $stamp_where = !empty($last_withdrawal_stamp) ? " AND t.start_time >= '{$last_withdrawal_stamp}'" : '';

        $query_str = "SELECT t_id FROM tournament_entries te
                          LEFT JOIN tournaments AS t ON t.id = te.t_id
                        WHERE te.user_id = {$user->getId()} AND te.status = 'finished'
                            AND t.start_format = 'sng'
                            AND t.category NOT IN ('freeroll', 'jackpot')
                            AND t.registered_players <= 9 {$stamp_where}";

        phive()->dumpTbl('bos-flag-query', ['query' => $query_str]);

        $query_res = phive('SQL')->sh($user->getId())->loadArray($query_str);
        if (empty($query_res)) {
            return false;
        }

        $t_ids = phive("SQL")->makeIn(phive()->arrCol($query_res, 't_id', 't_id'));

        //Checks unfinished battles which the user HAS ALREADY STARTED
        $query = "SELECT
                      COUNT(te4.id)                          AS total,
                      COUNT(IF(te4.spins_left > 0, 1, NULL)) AS total_left,
                      te4.t_id
                    FROM (
                           SELECT
                             sub.t_id,
                             SUM(sub.spins_left) AS total_spins_left,
                             SUM(t.xspin_info * t.spin_m) as total_spins
                           FROM tournament_entries AS sub
                             LEFT JOIN videoslots.tournaments t ON t.id = sub.t_id
                            WHERE sub.t_id IN ($t_ids)
                           GROUP BY sub.t_id
                           HAVING total_spins_left > 0 AND total_spins_left <> total_spins
                         ) AS sub2
                      LEFT JOIN tournament_entries te4 ON te4.t_id = sub2.t_id
                      WHERE te4.user_id = {$user->getId()}
                    GROUP BY te4.t_id";

        $query_res = phive('SQL')->shs('merge', '', null, 'tournament_entries')->loadArray($query);
        phive()->dumpTbl('bos-flag-res', $query_res);
        $tmp = [];
        foreach ($query_res as $elem) {
            $tmp[$elem['t_id']]['total'] += $elem['total'];
            $tmp[$elem['t_id']]['total_left'] += $elem['total_left'];
        }

        $end_time = microtime(true) - $start_time;
        if ($end_time > 1) {
            phive()->dumpTbl('slow-bos-flag-query-log', $end_time, $user->getId());
        }

        $percentage = phive('Config')->getValue('withdrawal-flags', 'unfinished-sng-percentage-threshold', 100);
        foreach ($tmp as $key => $elem) {
            if (($elem['total_left'] * 100 / $elem['total']) >= $percentage) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true where majority of spins for a users has been done in a sng battle.
     * @param $user_id
     * @param $last_withdrawal_stamp
     * @return bool
     */
    public function isMajoritySngTournaments($user_id, $last_withdrawal_stamp)
    {
        $stamp_where = !empty($last_withdrawal_stamp) ? " AND t.start_time >= '{$last_withdrawal_stamp}'" : '';

        $normal_spins_outside_battles = phive('SQL')->sh($user_id)->loadAssoc("
            SELECT
                SUM(bet_cnt) AS total_spins
            FROM users_game_sessions AS t
            WHERE user_id = {$user_id} {$stamp_where}
        ")['total_spins'];
        if (empty($normal_spins_outside_battles)) {
            return false;
        }

        $sng = phive('SQL')->sh($user_id)->loadAssoc("
            SELECT
                start_format,
                SUM(t.xspin_info * t.spin_m) AS total_spins
            FROM tournament_entries te
                LEFT JOIN tournaments t ON t.id = te.t_id
            WHERE te.status = 'finished'
                AND te.user_id = {$user_id}
                AND t.category NOT IN ('freeroll', 'jackpot')
                AND registered_players <= 9
                AND start_format = 'sng' {$stamp_where}
            GROUP BY start_format
        ")['total_spins'];
        if (empty($sng)) {
            return false;
        }

        return $sng > $normal_spins_outside_battles;
    }

    /**
     *
     * @param DBUser $user
     * @param array $config
     *
     * @return string
     */
    public function checkBonusAbuse(DBUser $user, array $config)
    {
        if (!empty($config['bonus-abuse-fraud-flag']) && $config['bonus-abuse-fraud-flag'] == 'yes') {
            if ($user->registerSince() <= (int)$config['bonus-abuse-fraud-flag-days']) {
                $bonus_ratio = phive('SQL')->sh($user)
                    ->loadValue("SELECT (SUM(ct.amount) / (SELECT sum(bet_amount) FROM users_game_sessions ugs
                                    WHERE ugs.user_id = {$user->getId()})) * 100 as ratio
                                        FROM cash_transactions ct
                                    WHERE ct.user_id = {$user->getId()} AND transactiontype IN (32,31,51,66,69,74,77,80,82,84,85,86);"
                    );
                if ((float)$bonus_ratio > (float)$config['bonus-abuse-fraud-flag-percent']) {
                    $user->setSetting('bonus_abuse-fraud-flag', 1);
                    return "Bonus/wager ratio: {$bonus_ratio}%";
                }
            }
        }
        return '';
    }

    /**
     * TODO check if the account was verified by checking the date of creation and if it is not blocked we flag those cases
     *
     * @param int $user
     * @param $instadebit_user_id
     */
    public function instadebitFraudChecks($user_id, $instadebit_user_id, $transactionId)
    {
        $user = cu($user_id);

        if (!$user->hasSetting('instadebit_user_id')) {
            $user->setSetting('instadebit_user_id', $instadebit_user_id);
        }

        $account_info = $this->getInstadebitAccountInfo($instadebit_user_id, $user);
        if (!empty($account_info)) {

            if (!$user->hasSetting('instadebit_data')) {
                $user->setSetting('instadebit_data', json_encode($account_info));
            }

            $flag = false;

            if (count($account_info) > 1) {
                $flag[] = 'Multiple accounts found in Instadebit';
            }

            if ($account_info[0]['status'] != 'Active') {
                $flag[] = "Status on Instadebit is {$account_info[0]['status']}";
            }

            $date = date_create_from_format('M d, Y', $account_info[0]['opened_at']);

            if($date->diff(new \DateTime())->days < 8) {
                $flag[] = 'Instadebit account created less than 8 days ago';
            }

            // looping in case we get more than 1 account from instadebit BO crawling.
            foreach($account_info as $account) {
                if(phive('Cashier/Fr')->instadebitFullnameMismatch($user, $account)) {
                    $flag[] = "User has mismatching information against instadebit '{$account['firstname']} {$account['lastname']}'";
                }
            }

            if ($flag !== false) {
                phive()->dumpTbl('instadebit-fraud', [$flag, $account_info, $user_id], $user_id);

                $log_desc = "Account flagged due to: ". implode(',', $flag);
                phive('UserHandler')->logAction($user_id, $log_desc, 'instadebit-flag');
                if (phive('Cashier')->getSetting('instadebit-fraud-flag', false) === true) {
                    InstadebitFraudFlag::create($transactionId)->assign($user, AssignEvent::ON_DEPOSIT_START);

                    $log_id = phive('UserHandler')->logAction($user_id, "set-fraud-flag|fraud - {$log_desc}", 'intervention');
                    /** @uses Licensed::addRecordToHistory() */
                    lic('addRecordToHistory', [
                        'intervention_done',
                        new InterventionHistoryMessage([
                            'id'                => (int) $log_id,
                            'user_id'           => (int) $user_id,
                            'begin_datetime'    => phive()->hisNow(),
                            'end_datetime'      => '',
                            'type'              => 'set-fraud-flag',
                            'cause'             => 'fraud',
                            'event_timestamp'   => time(),
                        ])
                    ], $user);
                }
            } else {
                InstadebitFraudFlag::create()->revoke($user, RevokeEvent::ON_DEPOSIT_START);
            }
        } else {
            phive()->dumpTbl('instadebit-fraud', ['Account info not found', $account_info, $user_id], $user_id);
            phive('UserHandler')->logAction($user, "Error while trying to check the instadebit name data.", 'instadebit-fraud-check');
        }
    }

    /**
     * We scrap the Instadebit BO to get the user information from them
     *
     * @param string $instadebit_user_id For example "LOUIS+DOYLE+PP88TJNQ"
     * @param DBUser $user
     * @return array[]|false
     */
    public function getInstadebitAccountInfo($instadebit_user_id, $user)
    {
        try {
            $settings = phive('Cashier')->getSetting('instadebit_backoffice');
            if (empty($settings)) {
                return false;
            }

            $settings = phive('Cashier')->filterSettingsByCountryAndProvince($settings, $user);

            $client = new class extends GoutteClient {
                protected function getAbsoluteUri($uri)
                {
                    return parent::getAbsoluteUri(preg_replace('/^http:\/\//', 'https://', $uri));
                }
            };

            $crawler = $client->request('GET', "https://{$settings['hostname']}/merchant/mvc/merchantLogin_init.do");

            $form = $crawler->selectButton('Submit')->form();
            $form['admin_id'] = $settings['username'];
            $form['password'] = $settings['password'];
            $res = $client->submit($form);

            if ($res->filterXPath('//*[@id="login.errors"]')->count() > 0) {
                phive('MailHandler2')->mailLocal('Instadebit Backoffice Error', "System failed to login in Instadebit Backoffice", 'dev_support_mail');
            }

            $crawler = $client->request('GET', "https://{$settings['hostname']}/merchant/mvc/checkCustomerAccountStatus_init.do");

            $form = $crawler->selectButton('Submit')->form();

            $form['customerID'] = $instadebit_user_id;

            $crawler = $client->submit($form);

            $data = $crawler->filter('.trnormal')->each(function (\Symfony\Component\DomCrawler\Crawler $node) {
                return $node->filter('td')->each(function (\Symfony\Component\DomCrawler\Crawler $node) {
                    return $node->text();
                });
            });

            if (empty($data)) {
                $data = $crawler->filter('.trredhighlight')->each(function (\Symfony\Component\DomCrawler\Crawler $node) {
                    return $node->filter('td')->each(function (\Symfony\Component\DomCrawler\Crawler $node) {
                        return $node->text();
                    });
                });
            }

            $res = array_map(function ($elem) {
                return [
                    'firstname' => $elem[0],
                    'lastname' => $elem[1],
                    'province' => $elem[2],
                    'city' => $elem[3],
                    'opened_at' => $elem[4],
                    'ip' => $elem[5],
                    'status' => $elem[6]
                ];
            }, $data);

            phive()->dumpTbl('instadebit_account_info', $res, $user);
            return $res;

        } catch (\Exception $e) {
            phive()->dumpTbl('instadebit_account_info_error', "Instadebit fetch account failed: {$e->getMessage()}", $user);
            return false;
        }
    }

    /*
       1.) Get all user ids that have made a successful withdrawal.
       2.) Loop user ids and:
       2.1) Get newest row in money laundry.
       2.2) Get all withdrawals since then or all if there was no money laundry line.
       2.3) Insert new money laundry line with the help of the ml line and oldest withdrawal line if there was an ml line. Shorten withdrawal array by one.
       2.4) Loop the rest of the withdrawal rows in pairs of two and create ml rows with them, abort if we're "unpaired".
     */
    /*
    function moneyLaundryCron(){
        $str = "SELECT user_id FROM pending_withdrawals WHERE status = 'approved' GROUP BY user_id";
        foreach($this->db->load1DArr($str, 'user_id') as $uid){
            $ml          = $this->db->loadAssoc("SELECT w_id2, user_id FROM money_laundry WHERE user_id = $uid ORDER BY id DESC LIMIT 0,1");
            $where_extra = empty($ml) ? '' : "AND id > {$ml['w_id2']}";
            $out_sql     = "SELECT * FROM pending_withdrawals WHERE status = 'approved' AND user_id = $uid $where_extra";
            $outs        = $this->db->sh($uid, '', 'pending_withdrawals')->loadArray($out_sql);
            if(empty($outs))
                continue;

            if(!empty($ml)){
                $w1 = phive('Cashier')->getPending($ml['w_id2'], $uid);
                $w2 = array_shift($outs);
                $this->insertRow($uid, $w1, $w2);
            }

            $pw = $w1 = $w2 = [];
            while(!empty($outs)){
                $w1 = array_shift($outs);
                $w2 = array_shift($outs);

                if(!empty($w1) && !empty($pw))
                    $this->insertRow($uid, $pw, $w1);

                if(!empty($w1) && !empty($w2))
                    $this->insertRow($uid, $w1, $w2);
            }

        }
    }
    */

    /*
    function moneyLaundryFix()
    {
        $this->db->query("DELETE FROM money_laundry WHERE w_id1 = 0");

        $ml_list = $this->db->loadArray("
            SELECT ml.*, pw2.currency as w2_currency,
              (
                SELECT SUM(amount) FROM deposits d WHERE d.user_id = ml.user_id AND d.timestamp BETWEEN ml.w_stamp1 AND ml.w_stamp2
              ) as real_dep_sum,
              (
                SELECT count(d.id) FROM deposits d WHERE d.user_id = ml.user_id AND d.timestamp BETWEEN ml.w_stamp1 AND ml.w_stamp2
              ) as real_dep_count
            FROM money_laundry ml
            LEFT JOIN pending_withdrawals pw2 ON pw2.id = ml.w_id2
            WHERE ml.id > 77615 AND ml.dep_cnt = 0
        ");
        foreach ($ml_list as $ml_row) {
            if ($ml_row['real_dep_count'] == 0 && !empty($ml_row['currency'])) {
                continue;
            } else {
                $this->db->query("UPDATE money_laundry
                                  SET dep_sum = {$ml_row['real_dep_sum']}, dep_cnt = {$ml_row['real_dep_count']}, currency = {$ml_row['currency']}
                                  WHERE id = {$ml_row['id']}");
            }
        }

        $no_currency_list = $this->db->loadArray("
            SELECT ml.*, pw2.currency as w2_currency
                FROM money_laundry ml
                  LEFT JOIN pending_withdrawals pw2 ON pw2.id = ml.w_id2
                WHERE ml.currency = ''
        ");
        foreach ($no_currency_list as $nc_row) {
            $this->db->query("UPDATE money_laundry
                                  SET currency = {$nc_row['currency']}
                                  WHERE id = {$nc_row['id']}");
        }

        echo "END";
    }
    */

    function getSum($uid, $tbl, &$w1, &$w2, $t_field = 'created_at'){
        $t_range = $this->db->tRng($w1['timestamp'], $w2['timestamp'], $t_field);
        return $this->db->sh($uid, '', $tbl)->loadAssoc("SELECT SUM(amount) AS amount, COUNT(*) AS cnt FROM $tbl WHERE user_id = $uid $t_range");
    }

    function insertRow($uid, &$w1, &$w2, $wager = null, $dep = null){
        if (empty($wager))
            $wager = $this->getSum($uid, 'bets', $w1, $w2);
        if (empty($dep))
            $dep   = $this->getSum($uid, 'deposits', $w1, $w2, 'timestamp');
        $this->db->sh($uid, '', 'money_laundry')->insertArray('money_laundry', [
            'user_id'   => $uid,
            'w_id1'     => $w1['id'],
            'w_id2'     => $w2['id'],
            'w_stamp1'  => $w1['timestamp'],
            'w_stamp2'  => $w2['timestamp'],
            'dep_sum'   => $dep['amount'],
            'dep_cnt'   => $dep['cnt'],
            'wager_sum' => $wager['amount'],
            'currency'  => $w1['currency']
        ], null, false, false);
    }

    function hasRuleSets(&$u){
        if(empty($this->countries))
            $this->countries = $this->db->load1DArr("SELECT * FROM fraud_rules WHERE country != '' GROUP BY country", 'country');
        $country = is_object($u) ? $u->getCountry() : $u['country'];
        return in_array($country, $this->countries);
    }

    /*
    function filterRules($rules, $ud, $game = ''){
        $rules = array_filter($rules, function($rule) use($ud, $game){
            if(empty($game) && $rule['tbl'] == 'micro_games')
                return false;
            return true;
        });
        return $rules;
    }
    */

    function matchRule($rule, $result){
        if(!empty($result)){
            $this->matched_rules[] = $rule;
            return true;
        }else{
            if(!$this->testAltRule($rule, $ud)){
                $this->failed_rules[] = $rule;
                return false;
            }else
                return true;
        }
    }

    function testAltRule($failed_rule, &$ud){
        if(!empty($failed_rule['alternative_ids'])) {
            // get alternative rules based on id's
            $alternative_rules = $this->db->loadArray("SELECT * FROM fraud_rules WHERE id IN({$rule['alternative_ids']})");
            // update $count_rules, because 1 rule will be replaced by 1 or more rules
            $count_alternative = count($alternative_rules);
            $this->count_rules = $this->count_rules + ($count_alternative - 1);

            foreach ($alternative_rules as $key => $alternative_rule) {
                if($alternative_rule['tbl'] == 'location') {
                    $result = phive('IpBlock')->getLongLat($ud['reg_ip'], $alternative_rule['field']);
                    //$result = phive('IpBlock')->getGeoIpRecord($ud['reg_ip']);
                    if((string)$result == $alternative_rule['like_value'])
                        $this->matched_rules[] = $alternative_rule['id'];
                    else
                        return false;
                }

                if($alternative_rule['tbl'] == 'user') {
                    if(!$this->checkRule($ud, $alternative_rule))
                        return false;
                }
            }
            return true;
        }
        return false;
    }

    function getDb($rule, &$ud){
        return $this->db->isSharded($rule['tbl']) ? $this->db->sh($ud['id']) : $this->db;
    }

    // TODO henrik remove if not used.
    function checkAllRulesets(&$u, $game){
        $tags = $this->db->loadCol("SELECT tag FROM fraud_groups WHERE is_active = 1", 'tag');
        return $this->checkIsolatedRulesets($tags, $u, $game);
    }

    // TODO henrik remove if not used.
    // Loops all tags and checks each tag in isolation, if tag1 doesn't match we continue to tag2 and so on until
    // all tags did not match or until one tag matches, then we return the result (not false).
    function checkIsolatedRulesets($tags, &$u, $game = ''){
        foreach($tags as $tag){
            $res = $this->checkRuleset([$tag], $u, $game);
            if($res !== false){
                return [$tag, $res];
            }
        }
        return false;
    }

    /**
     * TODO henrik remove if not used.
     *  Example:
     *  tag: basic -> column
     *  Country: GB -> column
     *  Dob: 1990-01-01 – 1999-12-31 -> table = users -> field -> dob, start_value -> 1990-12-31, field -> dob, end_value -> 1999-12-31 -> two rows
     *  Email: contains yahoo.co.uk user_field -> email, like_value -> %yahoo.co.uk
     *  Ip (do we need to convert to raw number to compare range?, if so we need special handling, can't just do ><):
     *       213.205.194.1 – 213.205.194.254 -> user_field -> reg_ip, user_start_value -> 213.205.194.1, user_field -> dob, user_end_value -> 213.205.194.254 -> two rows
     *  Deposit: VISA with a card number that starts with 4462 91 -> table = deposits, field = scheme, like_value = '4462 91%'
     *  Bonuses: Has never activated any -> table = bonus_entries, field = user_id, value_exists
     *  Game: Opens a game from the category black jack -> table = micro_games,
     *
     * fraud_rules structure:
     * id (primary, ai blabla)
     * country (varchar 2)
     * tag (varchar 55)
     * tbl (varchar 55)
     * field (varchar 55)
     * start_value (varchar 55)
     * end_value (varchar 55)
     * like_value (varchar 55)
     *
     * @param array $tags
     * @param DBUser $u
     * @param array $game
     * @return bool  True only if all rules are matched
     */
    function checkRuleset($tags, &$u, $game = ''){
        if(!$this->hasRuleSets($u))
            return false;
        $ud = is_object($u) ? $u->data : $u;
        foreach($tags as $tag) {
            if($this->test_ruleset === true){
                $sql = "SELECT * FROM fraud_groups fg
                            INNER JOIN fraud_rules fr ON fr.group_id = fg.id
                        WHERE (fr.country = '{$ud['country']}' OR fr.country = '')
                            AND fg.tag = '{$tag}'";
            }else{
                $sql = "SELECT * FROM fraud_groups fg
                        INNER JOIN fraud_rules fr ON fr.group_id = fg.id
                    WHERE fg.is_active = 1
                        AND (fr.country = '{$ud['country']}' OR fr.country = '')
                        AND fg.tag = '{$tag}'
                        AND
                        (
                        (NOW() BETWEEN start_date AND end_date)
                        OR (start_date is null AND end_date is null)
                        OR (start_date is null AND end_date > NOW())
                        OR (start_date < NOW() AND end_date is null)
                    )";
            }

            //echo $sql;

            $rules = $this->db->loadArray($sql);
            //$rules               = $this->filterRules($this->db->loadArray($sql), $ud, $game);
            $this->count_rules   = count($rules);
            $this->matched_rules = array();
            $this->failed_rules  = array();

            //print_r($rules);

            if(empty($rules))
                return false;

            foreach($rules as $rule) {

                if($rule['tbl'] == 'users') {
                    //check directly with $ud
                    if(!$this->checkRule($ud, $rule) && !$this->debug)
                        return false;
                } else {
                    // What is the point of having one column for each "action" when we only check one column per rule anyway?
                    // Could we refactor the table structure to have an action column that has the value "like_value", "value_not_in" etc? /Henrik
                    //do query with the help of tbl etc
                    if($rule['like_value'] !== '') {

                        if(!in_array($rule['tbl'], ['micro_games', 'location'])) {
                            $sql = "SELECT * FROM {$rule['tbl']} WHERE {$rule['field']} LIKE '{$rule['like_value']}' AND user_id = '{$ud['id']}'";
                            $result = $this->getDb($rule, $ud)->loadArray($sql);
                            if(!$this->matchRule($rule, $result) && !$this->debug){
                                return false;
                            }
                        }

                        if($rule['tbl'] == 'micro_games'){
                            if($this->test_ruleset === true)
                                continue;

                            // If there is no game and we're not testing we return false, ie no ruleset with this rule can ever match when for insance
                            // making a deposit.
                            if(!$this->matchRule($rule, $game['tag'] == $rule['like_value']) && !$this->debug)
                                return false;
                        }

                        if($rule['tbl'] == 'location') {
                            $result = phive('IpBlock')->getLongLat($ud['reg_ip'], $rule['field']);
                            //$result = phive('IpBlock')->getGeoIpRecord($ud['reg_ip']);
                            if(!$this->matchRule($rule, (string)$result == $rule['like_value']) && !$this->debug)
                                return false;
                        }

                    } elseif($rule['value_not_in'] !== '') {
                        // We need to have values that are not in the looked for values, eg bet date not in '2016-12-01','2016-12-05', empty result
                        // does not match, logic in this case is that the criminals has had to have made bets on other days than those two. /Henrik
                        $sql = "SELECT * FROM {$rule['tbl']} WHERE {$rule['field']} NOT IN({$rule['value_not_in']}) AND user_id = '{$ud['id']}'";
                        $result = $this->getDb($rule, $ud)->loadArray($sql);
                        if(!$this->matchRule($rule, $result) && !$this->debug){
                            return false;
                        }
                    } elseif($rule['value_in'] !== '') {
                        $sql = "SELECT * FROM {$rule['tbl']} WHERE {$rule['field']} IN({$rule['value_in']}) AND user_id = '{$ud['id']}'";
                        $result = $this->getDb($rule, $ud)->loadArray($sql);
                        if(!$this->matchRule($rule, $result) && !$this->debug){
                            return false;
                        }
                    } elseif($rule['value_exists'] !== '') {
                        $sql = "SELECT * FROM {$rule['tbl']} WHERE {$rule['field']} = {$ud['id']} LIMI 0,1";
                        $result = $this->getDb($rule, $ud)->loadAssoc($sql);
                        if(!$this->matchRule($rule, $result) && !$this->debug)
                            return false;
                    } elseif($rule['value_does_not_exist'] !== '') {
                        // We check for if field (typically user_id) is present or not, if it is not present
                        // we have a rule match.
                        $sql = "SELECT * FROM {$rule['tbl']} WHERE {$rule['field']} = {$ud['id']} LIMIT 0,1";
                        $result = $this->getDb($rule, $ud)->loadAssoc($sql);
                        $result = empty($result) ? true : false;
                        if(!$this->matchRule($rule, $result) && !$this->debug)
                            return false;

                    } elseif($rule['not_like_value'] !== '') {
                        $sql = "SELECT * FROM {$rule['tbl']} WHERE {$rule['field']} NOT LIKE '{$rule['not_like_value']}' AND user_id = '{$ud['id']}'";
                        $result = $this->getDb($rule, $ud)->loadArray($sql);
                        if(!$this->matchRule($rule, $result) && !$this->debug)
                            return false;

                    } else {
                        // query with start and end value

                    }
                }
            }
        }
        if(!$this->debug)
            return true;
        if($this->count_rules === count($this->matched_rules))
            return true;
        return ['failed_rules' => $this->failed_rules, 'matched_rules' => $this->matched_rules];
    }

    /**
     *
     * @param array $arr
     * @param array $rule
     * @return bool
     */
    function checkRule(&$arr, $rule){
        if(!empty($rule['like_value'])) {

            if(preg_match('/^%/', $rule['like_value'])) {
                $string = str_replace('%', '', $rule['like_value']);
                if(!$this->matchRule($rule, preg_match("/{$string}$/", $arr[$rule['field']])) && !$this->debug)
                    return false;
            }

            if(preg_match('/%$/', $rule['like_value'])) {
                $string = str_replace('%', '', $rule['like_value']);
                if(!$this->matchRule($rule, preg_match("/^{$string}/", $arr[$rule['field']])) && !$this->debug)
                    return false;
            }

        } elseif (!empty($rule['value_exists'])) {
            // Is this necessary, why not just use the $arr which is the same as user data? /Henrik
            $value = $this->db->loadArray("SELECT * FROM {$rule['tbl']} WHERE {$rule['field']} = '{$arr[$rule['field']]}'");
            if(!$this->matchRule($rule, !empty($value)) && !$this->debug)
                return false;

        } else {
            // check with start and end value
            if($rule['field'] == 'reg_ip') {

                if(!$this->matchRule($rule, ip2long($arr[$rule['field']]) >= ip2long($rule['start_value']) && ip2long($arr[$rule['field']]) <= ip2long($rule['end_value'])) && !$this->debug)
                    return false;

            } else {

                $res = $arr[$rule['field']] >= $rule['start_value'] && $arr[$rule['field']] <= $rule['end_value'];
                if(!$this->matchRule($rule, $res) && !$this->debug)
                    return false;
            }
        }
        return true;
    }


}
