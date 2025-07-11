<?php

use Carbon\Carbon;
use Carbon\CarbonInterface;

require_once('Arf.php');

class Rg extends Arf {

    private $current_day_last_x_bets = [];
    private $last_day_average_bets = [];


    /** @var DBUserHandler $uh */
    public DBUserHandler $uh;

    function __construct() {
        parent::__construct();
        $this->t = phive('Trophy');
    }

    /* MAIN METHODS */

    function onDeposit($uid) {
        $u = cu($uid);
        $this->checkNetDepositThreshold($u);
        $this->hasXNetDepositInLastYDays($u);
        $this->hasXNetDepositInLastYHours($u);
        $this->returningSelfLockers($u);
        $this->affordabilityCheck($u);
        $this->vulnerabilityCheck($u);
        $this->highRiskDepositor($u);
        $this->changeIncreaseDeposit($u);
        $this->highDepositFrequency($u);
        $this->returningSelfExcluders($u);

        $day = phive()->today();
        $user_id = $u->getId();
        $score = $this->getLatestRatingScore($user_id, 'RG');
        $this->changeDepositPattern($user_id, $score, $day);
    }

    public function onWithdrawal($uid)
    {
        $u = cu($uid);
        $this->hasXNetDepositInLastYDays($u);
        $this->hasXNetDepositInLastYHours($u);
    }

    /**
     * These triggers are fired from the following scenarios:
     * - callback from MTS to mts_fail_notify.php
     * - redirect to cashier iframe with "?status=failed" OR "action=fail"
     * - Cashier/DepositStart::init() OR Cashier/DepositStart::execute() returning an error.
     *
     * @param $uid
     */
    public function onFailedDeposit($uid): void
    {
        $u = cu($uid);
        $this->declinedDeposits($u);
        $this->velocityFailedDeposits($u);
        $this->amountFailedDeposit($u);
    }

    function onLogin($uid)
    {
        // TODO BOAPI call is not done here anymore.. shall we move the try -> catch into Arf invoke?
        try {
            $u = cu($uid);
            $this->closedVsReOpenRate($u);
            $this->activeLinkedClosedAccounts($u);
        } catch (Exception $e) {
            phive('Logger')->error('RG-error', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Triggers on user session ends.
     * The user session - it's time between user logged in the system and user logout the system
     *
     * @param $uid
     * @return void
     */
    function onSessionEnd($uid) {
        $u = cu($uid);
        //$this->updateRatingScore($u); //TODO check for performance problems
        $this->extendedGamePlaySession($u);
        $this->sourceOfIncomeCheck($u);
    }

    /**
     * Triggers when game session ends
     * The game session - it is the time between start gameplay and end gameplay
     *
     * @param int $uid
     * @param int $game_session_id
     * @throws Exception
     */
    public function onGameSessionEnd($uid, $game_session_id) {
        $lock = false;
        $attempts = [0, 2, 2, 5, 10, 15, 30];
        $attempt = 0;
        while (!$lock) {
            $time = $attempts[$attempt];
            if ($time) {
                sleep($time);
            }
            if (array_key_exists($attempt + 1, $attempts)) {
                $attempt++;
            }
            $lock = phMsetNx('RG_UGS_' . $uid, $game_session_id, 30);
        }

        $user = cu($uid);
        $game_session = phive("SQL")->sh($user->getId())->loadAssoc("SELECT * FROM users_game_sessions WHERE id = {$game_session_id}");
        $this->gameSessionDuration($user);
        $this->gamePlayTime($user, $game_session);
        $this->checkLossAmountBasedOnNGR($user, $game_session);
        $this->averageBetIncreasedAgainstPreviousSession($user);
        $active_game_sessions = phive("SQL")
            ->sh($user->getId())
            ->loadAssoc("SELECT COUNT(id) as `count` FROM users_game_sessions WHERE user_id = {$user->getId()}
                                    AND end_time = '0000-00-00 00:00:00'"
            );

        if($active_game_sessions['count'] == 0) {
            rgLimits()->resetRc($user, [], true);
        }

        $day = phive()->today();
        $user_id = $user->getId();
        $score = $this->getLatestRatingScore($user_id, 'RG');
        $this->checkWagerPatternChangeDaily($user_id, $score, $day);
        $this->changeSessionTime($user_id, $score, $day);
        $this->hasPlayedXSpinsInLastYHours($user);
        $this->hasPlayedXHoursInLastYHours($user);

        phMdel('RG_UGS_' . $uid);
    }

    public function onRegistration($uid) {
        $u = cu($uid);
        $this->duplicateAccount($u);
        $this->selfExcluderLinksIP($u);
        $this->selfExcluderLinksDevice($u);
        $this->otherLinks($u);
    }

    /**
     * On withdrawal canceled by user
     * @param $uid
     */
    function onDisapprovePending($uid) {
        $u = cu($uid);
        $this->cancellationOfWithdrawals($u);
        $this->canceledLast24Hours($u);
        $this->canceledXTransactionsToday($u);
    }

    function onUserComment($uid, $tag) {
        return true; // we removed old RG10 so no action is done here for now - 2019-07-23
//        $u = cu($uid);
    }

    function onSetLimit($uid) {
        $u = cu($uid);
        $this->limitFrequencyChange($u);
    }

    function onEveryMinCron() {
        return true; // we removed old RG13 so no action is done here for now - 2019-07-23
    }

    /**
     * lastXSpinsAgainstPreviousXSpins goes first cause it will check on more bets, so we can reuse the query
     *
     * @param $u_obj
     * @param array $ins
     * @param array $cur_game
     */
    public function onBet($u_obj, $ins, $cur_game)
    {
        $this->t->memSet('curBetAmountRg', $u_obj->data, $cur_game, $ins['amount']);
    }

    /**
     * @param integer|DBUser $uid
     * @param $ins
     * @param $cur_game
     */
    public function onWin($ud, $ins, $cur_game) {
        $ud = ud($ud);

        $bet_amount = $this->t->memGet('curBetAmountRg', $ud, $cur_game);
        $win_amount = $ins['amount'];
        // In case of FS wins
        if (is_null($bet_amount)) {
            return;
        }
        $this->bigWinMultiplier($ud['id'], $bet_amount, $win_amount);
        $this->hadBigWinOfXTheirBet($ud['id'], $bet_amount, $win_amount);
    }

    /* END MAIN METHODS */

    /**
     * RG2
     * Device (fingerprint) linked to a historical Self Excluder
     *
     * @param DBUser $u_obj
     * @return bool
     */
    public function selfExcluderLinksDevice(DBUser $u_obj): bool
    {
        $trigger = 'RG2';

        $current_session = $u_obj->getCurrentSession();
        $cur_fingerprint = $current_session['fingerprint'];
        $start_time = phive()->hisMod('-90 day');
        $sql = "SELECT users_sessions.user_id, users.username
                FROM users_sessions
                INNER JOIN users
                ON users.id = users_sessions.user_id
                WHERE (fingerprint = '$cur_fingerprint' AND fingerprint != '')
                AND users.id != {$u_obj->getId()}
                AND created_at >= '{$start_time}'
                GROUP BY user_id";
        $users = phive('SQL')->shs()->loadArray($sql);

        return $this->logPreviouslySelfLockedAccounts($u_obj, $trigger, $users, ['fingerprint' => $cur_fingerprint]);
    }

    /**
     * RG3
     * Duplicate account opening by previous excluder on Name, Surname and DOB
     *
     * @param DBUser $u_obj
     * @return bool
     */
    public function duplicateAccount(DBUser $u_obj): bool
    {
        $trigger = 'RG3';

        // Get all users with the same Name, Surname and DOB; excluding current user
        $sql = "
            SELECT
                id as user_id, username
            FROM
                users
            WHERE
                firstname    = '{$u_obj->getAttribute('firstname')}'
                AND lastname = '{$u_obj->getAttribute('lastname')}'
                AND dob      = '{$u_obj->getAttribute('dob')}'
                AND id       != '{$u_obj->getId()}'
        ";
        $users = $this->replica->shs()->loadArray($sql);

        return $this->logPreviouslySelfLockedAccounts($u_obj, $trigger, $users);
    }

    /**
     * RG4
     * User has cancelled more than X withdrawals last 24 hours
     *
     * @param DBUser $user
     * @return bool
     */
    public function canceledLast24Hours(DBUser $user): bool
    {
        $trigger = 'RG4';

        return $this->hasMoreThanXWithdrawalCancellationsInTheLastXHours($user, $trigger);
    }

    /**
     * RG5
     * Account activity from previously closed accounts for self exclusion
     * @param DBUser $user
     * @return mixed|null
     */
    public function returningSelfExcluders($user) {
        $trigger = 'RG5';

        $allowed_jurisdictions = phive('Config')->valAsArray('RG', 'RG5-jurisdictions', ',');

        if (!in_array($user->getJurisdiction(), $allowed_jurisdictions, true)) {
           return false;
        }

        $unexcluded_date = $user->getSetting('unexcluded-date');
        $external_unexcluded_date = $user->getSetting('external-unexcluded-date');

        if (!$unexcluded_date && !$external_unexcluded_date) {
            return false;
        }

        $end_exclusion_date = Carbon::parse(max($unexcluded_date, $external_unexcluded_date))->toDateTimeString();
        $deposits = phive('Cashier')->getUserDeposits(
            $user->getId(),
            '',
            "status = 'approved' AND timestamp > '{$end_exclusion_date}'"
        );

        if (count($deposits) === 1) {
            $self_excluded_date = $user->getSetting('excluded-date');
            $external_excluded_date = $user->getSetting('external-excluded');
            $excluded_date = Carbon::parse(max($self_excluded_date, $external_excluded_date))->toDateTimeString();
            return phive('UserHandler')->logTrigger(
                $user,
                $trigger,
                "First deposit completed from an account which was externally self-excluded on {$excluded_date} and ended on {$end_exclusion_date}"
            );
        }
        return false;
    }

    /**
     * RG6
     * > or = 3 status change from open to closed where no exclusion was enforced
     * lock-date
     */
    function closedVsReOpenRate($u) {
        $trigger = 'RG6';
        $thold = $this->getAndCacheConfig('RG', $trigger, 2);

        $newest_stamp = phive('UserHandler')->getNewestTrigger($u, 'RG6')['created_at'];

        $user_id = $u->getId();
        $tot = $this->wasSelfLockedBefore($user_id, 'self-lock', $thold, 90, $newest_stamp);

        if (! empty($tot)) {
            $this->uh->logTrigger($u, $trigger, "Change from open to close {$tot} times");
            return true;
        }

        return false;
    }

    /**
     * RG7
     * User has cancelled more than 9 withdrawals last 168 hours
     *
     * @param DBUser $user
     * @return bool
     */
    public function cancellationOfWithdrawals(DBUser $user): bool
    {
        $trigger = 'RG7';

        return $this->hasMoreThanXWithdrawalCancellationsInTheLastXHours($user, $trigger);
    }

    /**
     * RG8
     * GPC logged session = or > 4 hours
     * We should trigger if they have 3 days with 4 hours each of the last 7 days
     */
    function extendedGamePlaySession($u) {

        $trigger = 'RG8';
        $user_id = $u->getId();

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, 7)) {
            return;
        }

        $hours_threshold = $this->getAndCacheConfig('RG', $trigger, 4);

        $sql = "SELECT
                  user_id,
                  DATE(start_time) as session_day,
                  TIME_FORMAT(SEC_TO_TIME(sum(secs)), '%Hh %im') as human_diff,
                  ROUND(sum(secs) / 3600,2) as hours_diff
                FROM (SELECT
                        t.*,
                        @time := if(@sum = 0, 0, TIME_TO_SEC(TIMEDIFF(start_time, @prevtime))) AS secs,
                        @prevtime := start_time,
                        @sum := @sum + isstart
                      FROM ((SELECT
                               user_id,
                               start_time,
                               1 AS isstart
                             FROM users_game_sessions t
                              WHERE t.user_id = {$user_id} AND bet_cnt > 0 AND end_time > NOW() - INTERVAL 7 DAY
                            )
                            UNION ALL
                            (SELECT
                               user_id,
                               end_time,
                               -1
                             FROM users_game_sessions t
                              WHERE t.user_id = {$user_id} AND bet_cnt > 0 AND end_time > NOW() - INTERVAL 7 DAY
                            )
                           ) t CROSS JOIN
                        (SELECT
                           @sum := 0,
                           @time := 0,
                           @prevtime := 0) vars
                      ORDER BY 1, 2
                     ) t
                GROUP BY DATE(start_time)
                HAVING hours_diff > {$hours_threshold};";

        $res = $this->replica->sh($user_id, '', 'users_game_sessions')->loadArray($sql);

        if (count($res) >= 3) {
            $msg = '';
            foreach ($res as $day) {
                $msg .= "Day: {$day['session_day']} session: {$day['human_diff']} | ";
            }
            // double check trigger log due to duplication issue
            if (!$this->uh->hasTriggeredLastPeriod($user_id, $trigger, 1, 'HOUR')) {
                $this->uh->logTrigger($u, $trigger, $msg);
            }
            return;
        }

        return;
    }

    /**
     * RG9
     * Limit (deposit, wager) number of changes >= 3 in 30 days
     *
     * @param DBUser $u
     * @return bool
     */
    function limitFrequencyChange(DBUser $u): bool
    {
        $trigger = 'RG9';
        $thold = $this->getAndCacheConfig('RG', $trigger, 3);
        $count = $u->getFrequencyChangeOfLimits();

        if ($count >= (int)$thold) {
            $data = json_encode([
                'attempts_of_limit_changing' => $count,
            ], JSON_THROW_ON_ERROR);
            $this->uh->logTrigger($u, $trigger, "= $count", true, false, '', $data);
            return true;
        }
        return false;
    }

    /**
     * Check for changes in deposit pattern, Ex. if user increase the number or the amount of the deposits he does.
     *
     * RG10 - Compare lifetime average deposit amount per transaction to current day and flag if average transaction sum has increased with X%
     * RG11 - Compare lifetime average deposit transactions per active day to current day and flag if average transactions sum has increased with X%.
     * RG12 - Compare lifetime average deposit sum per active day to current day and flag if deposit sum on current day has increased with X%.
     *
     * @param int $user_id
     * @param int $score
     * @param string $date
     * @return void
     */
    public function changeDepositPattern(int $user_id, int $score, string $date): void
    {
        $triggers = ['RG10', 'RG11', 'RG12'];
        $percentage = [];

        foreach ($triggers as $key => $trigger) {
            // if not in range or already triggered we skip the check.
            if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger, $score)) {
                unset($triggers[$key]);
            } else {
                $percentage[$trigger] = $this->getAndCacheConfig('RG', "{$trigger}-percentage", 10);
            }
        }

        if (empty($triggers)) {
            return;
        }

        //Using the deposits table because ndeposit in user_daily_stats doesn't work . the value is always 0 in live
        $sql = "
            SELECT
                DATE(timestamp) as date,
                SUM(amount) as amount,
                COUNT(id) as num_of_deposits
            FROM
                deposits
            WHERE
                user_id= {$user_id}
                AND timestamp <= '{$date} 23:59:59'
            GROUP BY date(timestamp)
            ORDER BY timestamp DESC
        ";
        $lifetime_deposits = $this->replica->sh($user_id)->loadArray($sql);
        // we remove current day from the full list, and if no deposit today we skip the checks.
        $today_deposits = array_shift($lifetime_deposits);
        if ($today_deposits['date'] !== $date) {
            return;
        }
        $today_deposits_count = $today_deposits['num_of_deposits'];
        $today_deposits_amount = $today_deposits['amount'];
        $lifetime_deposits_count = phive()->sum2d($lifetime_deposits, 'num_of_deposits');
        $lifetime_deposits_amount = phive()->sum2d($lifetime_deposits, 'amount');
        $lifetime_active_days_count = count($lifetime_deposits);

        $trigger = "RG10";
        if (array_search($trigger, $triggers) !== false) {
            $avg_today = $today_deposits_amount / $today_deposits_count;
            $avg_lifetime = $lifetime_deposits_amount / $lifetime_deposits_count;
            $res = $this->getChangeInPercentage($avg_today, $avg_lifetime);
            if ($res >= $percentage[$trigger]) {
                $res = nf2($res, true);
                $this->uh->logTrigger($user_id, $trigger, "Player increases the average deposit amount by {$res}% in comparison with the lifetime average deposit amount per transaction.");
            }
        }
        $trigger = "RG11";
        if (array_search($trigger, $triggers) !== false) {
            $avg_life = $lifetime_deposits_count / $lifetime_active_days_count;
            $res = $this->getChangeInPercentage($today_deposits_count, $avg_life);
            if ($res >= $percentage[$trigger]) {
                $res = nf2($res, true);
                $this->uh->logTrigger($user_id, $trigger, "Player increases the number of deposit by {$res}% in comparison with the lifetime number deposits");
            }
        }
        $trigger = "RG12";
        if (array_search($trigger, $triggers) !== false) {
            $avg_sum = $lifetime_deposits_amount / $lifetime_active_days_count;
            $res = $this->getChangeInPercentage($today_deposits_amount, $avg_sum);
            if ($res >= $percentage[$trigger]) {
                $res = nf2($res, true);
                $this->uh->logTrigger($user_id, $trigger, "Player increases average deposit sum per active day, by {$res}% in comparison with lifetime average deposit sum");
            }
        }
    }

    /**
     * RG13 - Flag if player increases their deposit on the current day with X% compared to previous average deposits on current day.
     *
     * @param $user
     * @param string $date
     * @return bool
     */
    function changeIncreaseDeposit($user, $date = '')
    {
        $user_id = $user->getId();
        $score = $this->getLatestRatingScore($user_id, 'RG');
        if(empty($score)) {
            return false;
        }
        if (empty($date)) {
            $date = phive()->today();
        }

        $trigger = "RG13";
        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return false;
        }

        $percentage[$trigger] = $this->getAndCacheConfig('RG', "{$trigger}-percentage", 10);

        $sql = "SELECT * FROM deposits WHERE user_id = {$user_id} AND timestamp >= '{$date}' ORDER BY id DESC";
        $deposits_today = phive('SQL')->sh($user_id)->loadArray($sql);
        if (count($deposits_today) <= 1) {
            return false;
        }
        $last_deposit = $deposits_today[0]['amount'];
        $previous_deposits = array_slice($deposits_today, 1);
        $avg_previous = phive()->sum2d($previous_deposits, 'amount') / count($previous_deposits);
        $res = $this->getChangeInPercentage($last_deposit, $avg_previous);
        if ($res > $percentage[$trigger]) {
            $res = nf2($res, true);
            $this->uh->logTrigger($user, $trigger, "Player increases the deposit by {$res}% compared to previous average deposits on current day. ", true, false);
        }
    }

    /**
     * RG14 Flag if player hits a big win with multiplier X on current day.
     */
    function bigWinMultiplier($user_id, $bet_amount, $win_amount)
    {
        $trigger = 'RG14';

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return;
        }

        $winx = (int)floor($win_amount / $bet_amount);

        $multiplier_triggers = $this->getAndCacheConfig('RG', "$trigger-multiplier", 10000);
        // The below is to support having several values, separated by space if wanting to speciy more than one multiplier.
        // However, only one per trigger and user is added at the moment.
        $multis = array_filter(explode(' ', $multiplier_triggers)); // Split and ignore empty ones.
        rsort($multis); // Sort in descending order.

        foreach ($multis as $multiplier) {
            if ($winx >= $multiplier) {
                $this->uh->logTrigger($user_id, $trigger, "Player triggers $multiplier multiplier big win.");
                break;
            }
        }
    }

    /**
     * RG14 (Sportsbook) Customer have triggered a big win that multiplied the last bet at least X times the current day.
     *
     * @param DBUser $user
     * @param int $ticket_id
     * @return void
     */
    function bigWinMultiplierAtTheSportsbook(DBUser $user, int $ticket_id)
    {
        $this->bigWinMultiplierAtSportTransaction($user, $ticket_id);
    }

    /**
     * RG14 (Poolx) Customer have triggered a big win that multiplied the last bet at least X times the current day.
     *
     * @param int $user_id
     * @param int $bet_id
     * @return void
     */
    function bigWinMultiplierAtThePoolx(int $user_id, int $bet_id)
    {
        $user = cu($user_id);
        if ($user) {
            $this->bigWinMultiplierAtSportTransaction($user, $bet_id);
        } else {
            phive()->dumpTbl('RG14', "Doesn't find user with ID: {$user_id}");
        }
    }

    /**
     * @param DBUser $user
     * @param int $ticket_id
     * @return void
     */
    private function bigWinMultiplierAtSportTransaction(DBUser $user, int $ticket_id)
    {
        $bet_amount = 0;
        $win_amount = 0;
        $ticket_transactions = phive('SQL')->sh($user)->arrayWhere('sport_transactions', ['ticket_id' => $ticket_id]);
        foreach ($ticket_transactions as $transaction) {
            if ($transaction['bet_type'] == 'bet') {
                $bet_amount = $transaction['amount'];
                continue;
            }

            if ($transaction['bet_type'] == 'win') {
                $win_amount = $transaction['amount'];
            }

            if ($this->isAltenarCashoutCase($transaction, $user)) {
                $win_amount = $transaction['amount'];
            }
        }

        if ($bet_amount && $win_amount) {
            $this->bigWinMultiplier($user->getId(), $bet_amount, $win_amount);
        }
    }

    /**
     * @param array $transaction
     * @param DBUser $user
     * @return bool
     */
    private function isAltenarCashoutCase(array $transaction, DBUser $user)
    {
        if ($transaction['network'] == 'altenar' && $transaction['bet_type'] == 'void') {
            $ticket_transactions_info = phive('SQL')->sh($user)->arrayWhere('sport_transaction_info', ['sport_transaction_id' => $transaction['id']])[0];

            return $ticket_transactions_info['transaction_type'] === 'CashoutBet' ?? false;
        }

        return false;
    }

    /**
     * RG15
     * Last Deposit =  100% Bet
     *
     * @param DBUser $u
     * @param integer $bet_id
     * @return bool
     */
    function lastDeposit100PercentBet($u, $bet_id) {
        $trigger = 'RG15';
        $user_id = $u->getId();

        $sql = "SELECT amount FROM bets WHERE bets.id = {$bet_id}";
        $res = $this->replica->sh($user_id, '', 'bets')->loadArray($sql);
        if (!empty($res[0]['amount'])) {
            $betEur = chg($u, $this->def_cur, $res[0]['amount']);
            $betEurCents = nfCents($betEur, true);
            if ($u->getAttr('cash_balance') <= 0) {
                $sql = "SELECT amount FROM deposits WHERE user_id = $user_id ORDER BY id DESC LIMIT 1";
                $res = $this->replica->sh($user_id, '', 'deposits')->loadArray($sql);
                $depEur = chg($u, $this->def_cur, $res[0]['amount']);
                if ($betEur == $depEur) {
                    $this->uh->logTrigger($u, $trigger, "Bet {$this->def_cur} $betEurCents");
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Account activity from previously closed accounts for self-lock
     * @param DBUser $user
     */
    function returningSelfLockers($user) {
        $trigger = 'RG16';
        if ($this->uh->hasTriggeredLastPeriod($user->getId(), $trigger, 7)) {
            return;
        }

        $pre_triggers = phive('SQL')->sh($user->getId())->loadArray("SELECT * FROM triggers_log WHERE user_id = {$user->getId()} AND trigger_name = '{$trigger}' ORDER BY created_at DESC");
        $pre_self_excluded_actions = phive('SQL')->sh($user->getId())->loadArray("SELECT * FROM actions WHERE target = {$user->getId()} AND tag = 'deleted_lock-hours' ORDER BY created_at DESC");

        if (count($pre_triggers) >= count($pre_self_excluded_actions)) { //same or more number of triggers than actions we don't trigger again for now
            return;
        } elseif (strtotime($pre_self_excluded_actions[0]['created_at']) > strtotime($pre_triggers[0]['created_at'])) {
            phive('UserHandler')->logTrigger($user, $trigger, "Activity from account which self lock ended on: {$pre_self_excluded_actions[0]['created_at']}");
            die('flag');
        }
    }

    /**
     * Return number of time that user was:
     * self-locked -> lockType self-lock
     * self-excluded -> lockType self-exclusion
     * both -> lockType empty
     * Return false if not self locked before in any way
     *
     * @param $user_id
     * @param string $lock_type
     * @param string $how_many_times
     * @param null|int $period
     * @param null|string $from_stamp
     * @return bool
     */
    private function wasSelfLockedBefore($user_id, string $lock_type = '', $how_many_times = '', $period = null, $from_stamp = null) { //this is ugly but I have not time to refactor the whole thing now
        switch ($lock_type) {
            case 'self-exclusion':
                $tag = 'excluded-date';
                break;
            case 'self-lock':
                $tag = 'lock-date';
                break;
            default :
                $tag = 'profile-lock';
                break;
        }

        $sql = "SELECT count(1) as tot FROM actions WHERE actor = target AND tag = '{$tag}' AND actor = {$user_id}";

        if (!empty($period)) {
            $period = (int)$period;
            $sql .= " AND created_at BETWEEN NOW() - INTERVAL $period DAY AND NOW()";
        }

        if (!empty($from_stamp)) {
            $sql .= " AND created_at >= '{$from_stamp}'";
        }

        if (!empty($how_many_times)) {
            $sql .= " GROUP BY actor HAVING COUNT(1) > $how_many_times";
        }

        $res = $this->replica->sh($user_id)->loadArray($sql);
        if (!empty($res[0]['tot']) && $res[0]['tot'] > 0) {
            return $res[0]['tot'];
        }
        return false;
    }

    /**
     * Loop all provided customers (same IP | device | information) and check if the accounts were self-excluded
     * before, if this happen we fire a trigger with all matching users.
     *
     * @param DBUser $u_obj
     * @param string $trigger
     * @param array $users
     * @param array $extra
     * @return bool
     */
    private function logPreviouslySelfLockedAccounts(DBUser $u_obj, string $trigger, array $users = [], array $extra = []): bool
    {
        $usernames = '';
        foreach ($users as $user) {
            if ($this->wasSelfLockedBefore($user['user_id'], 'self-exclusion')) {
                $usernames .= "u_{$user['username']}_u ";
            }
        }
        if (!empty($usernames)) {
            $extra = !empty($extra) ? json_encode($extra) : '';
            $this->uh->logTrigger($u_obj, $trigger, $usernames, true, true, '', $extra);
            return true;
        }
        return false;
    }

    /**
     * Loop all provided customers (same IP | device | information) and check if the accounts were self-excluded
     * currently, if this happen we fire a trigger with all matching users.
     *
     * @param DBUser $u_obj
     * @param string $trigger
     * @param array $users
     * @param array $extra
     * @return bool
     */
    private function logCurrentlySelfLockedAccounts(DBUser $u_obj, string $trigger, array $users = [], array $extra = []): bool
    {
        $usernames = '';
        foreach ($users as $user) {
            $user_to_check = cu($user['user_id']);
            $uh = phive('DBUserHandler');
            if ($uh->isSelfExcluded($user_to_check)) {
                $usernames .= "u_{$user['username']}_u ";
            }
        }
        if (!empty($usernames)) {
            $extra = !empty($extra) ? json_encode($extra) : '';
            $this->uh->logTrigger($u_obj, $trigger, $usernames, true, true, '', $extra);
            return true;
        }
        return false;
    }

    /**
     * RG17
     * IP linked to a user with an active Self-Exclusion
     * @param DBUser $u_obj
     * @return bool
     */
    public function selfExcluderLinksIP(DBUser $u_obj): bool
    {
        $trigger = 'RG17';

        $current_session = $u_obj->getCurrentSession();
        $ip              = $current_session['ip'];
        $start_time = phive()->hisMod('-90 day');
        $sql = "SELECT users_sessions.user_id, users.username
                FROM users_sessions
                INNER JOIN users
                    ON users.id = users_sessions.user_id
                WHERE (ip = '$ip' AND ip != '')
                AND users.id != {$u_obj->getId()}
                AND created_at >= '{$start_time}'
                GROUP BY user_id";
        $users = phive('SQL')->shs()->loadArray($sql);

        return $this->logCurrentlySelfLockedAccounts($u_obj, $trigger, $users, ['ip' => $ip]);
    }

    /**
     * RG18  fails X amount of deposit transactions on current day.
     *
     * @param $user
     * @return void
     */
    public function amountFailedDeposit($user): void
    {
        $user_id = $user->getId();
        $trigger = 'RG18';

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, 7)) {
            return;
        }

        $today = phive()->today();
        $already_trigger = date('Y-m-d', strtotime($this->uh->getTriggerCreationDate($user_id, $trigger))) === $today;
        if($already_trigger) {
            return;
        }

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return;
        }

        try {
            $failed_deposits = phive('Cashier/Mts')->arf('getFailedDeposits', [$user_id, $today.' 00:00:00']);
        } catch (Exception $e) {
            error_log($e->getMessage());
            phive('Logger')->error($trigger . "_amountFailedDeposit", "Unable to connect with MTS/getFailedDeposits for fetching failed deposits: {$e->getMessage()}");
            return;
        }

        if(empty($failed_deposits)) {
            return;
        }

        $failed_to_eur = chgCents($failed_deposits['currency'], 'EUR', $failed_deposits['amount'], 1, $today);
        $amount_trigger = $this->getAndCacheConfig('RG', "$trigger-amount", 10000);
        if ($failed_to_eur > $amount_trigger) {
            $data = json_encode([
                'amount_of_failed_deposit' => $failed_deposits['amount'],
            ], JSON_THROW_ON_ERROR);
            $this->uh->logTrigger(
                $user,
                $trigger,
                "Player fails {$failed_deposits['amount']} cents in deposit transactions across {$failed_deposits['count']} transactions.",
                true,
                false,
                '',
                $data
            );
        }
    }

    /**
     * RG checks to run on everyhour CRON.
     *
     * @param string $day
     */
    public function everyHourCron($day = ''): void
    {
        if (empty($day)) {
            $day = phive()->today();
        }

        error_log("RG1". date('Y-m-d H:i:s'));

        $this->lostMoreThanXEuroInTheLastXDays($day);
        $this->lostMoreThanXEuroInTheLastXDaysAtTheSportsbook($day);

        $this->getPlayersByDay($day);

        error_log("RG2". date('Y-m-d H:i:s'));

        $this->intendedGambling($day);
        $this->unfreezeRgGrsCalculation();
    }

    /**
     * RG19
     *
     * Trigger people that their losses are below a threshold taking in consideration the amount declared in the source
     * of funds declaration, deduction taxes and taking a 20% for example (everything as configurable) with a new
     * RG trigger. Base it on the top part of the bracket. Set a different threshold on each bracket.
     * When the flag triggers we need to send an email
     *
     * @param DBUser $user
     */
    public function sourceOfIncomeCheck($user)
    {
        if (!in_array($user->getCountry(), phive('Config')->valAsArray('RG', 'RG19-countries'))) {
            return;
        }

        $trigger_name = 'RG19';
        $user_id = $user->getId();

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger_name, 30)) {
            return;
        }

        $documents = phive('Dmapi')->getUserDocumentsV2($user_id);
        $candidate_doc = false;
        foreach ($documents as $document) {
            if ($document['tag'] == 'sourceoffundspic' && $document['status'] == 'approved') {
                $candidate_doc = $document;
                break;
            }
        }

        if (empty($candidate_doc)) {
            return;
        }

        $parameters = $this->getRG19ThresholdParameters($user->getCountry(), $candidate_doc['source_of_funds_data']['annual_income']);
        $net_deposit = $this->getNetDeposit($user);

        if ($net_deposit <= 0) {
            return;
        }

        if ($net_deposit >= $parameters['threshold']) {
            $net_income = round($parameters['net_income'], 2);
            $description = "Monthly net income: {$net_income}, month net deposit: {$net_deposit}, threshold applied: {$parameters['bracket_threshold']}%";
            $res = $this->uh->logTrigger($user, $trigger_name, $description);

            try {
                if (!empty($res)) {
                    $emails = $this->getAndCacheConfig('RG', 'RG19-email-notification', 'rg@videoslots.com');
                    if (!empty($emails)) {
                        foreach ($emails as $k => $email) {
                            phive('MailHandler2')->sendRawMail($email, "RG19 trigger notification", "RG19 trigger executed on {$user->getUsername()}. {$description}");
                        }
                    }
                }
            } catch (Exception $e) {
                phive()->dumpTbl('RG19', "Error sending email: {$e->getMessage()}");
            }
        }
    }

    public function getRG19ThresholdParameters(string $country, string $annual_income): array
    {
        $taxes_per_country = phive('Config')->valAsArray('RG', 'RG19-tax', ' ', ':'); //'default:20 GB:20'

        if (empty($taxes_per_country['default'])) {
            $taxes_per_country = ['default' => 20];
        }

        $bracket_threshold = phive('Config')->valAsArray('RG', 'RG19-threshold', ' ', ':'); //'1:20 2:20 3'
        if (empty($bracket_threshold[1])) {
            $bracket_threshold = [1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20];
        }

        $annual_income_map = [
            '0-200,000kr' => [1, 200000],
            '200,000-400,000kr' => [2, 400000],
            '400,000-600,000kr' => [3, 600000],
            '600,000-800,000kr' => [4, 800000],
            '800,000-1,000,000kr' => [5, 1000000],
            '1,000,000kr +' => [6, 1000000]
        ];

        $currencies = phive('Currencer')->getAllCurrencies();
        $no_cur_map = [
            "0-20,000" => [1, 20000],
            "20,000-40,000" => [2, 40000],
            "40,000-60,000" => [3, 60000],
            "60,000-80,000" => [4, 80000],
            "80,000-100,000" => [5, 100000],
            "100,000+" => [6, 100000]
        ];

        foreach ($currencies as $currency) {
            foreach ($no_cur_map as $k => $v) {
                $annual_income_map[$currency['symbol'] . $k] = $v;
            }
        }

        $income_array = $annual_income_map[$annual_income];
        $bracket = $income_array[0];
        $top_income = $income_array[1];

        $tax = $taxes_per_country[$country] ?? $taxes_per_country['default'];
        $net_income = ($top_income - ($top_income * $tax / 100)) / 12; //Per month
        $threshold = $net_income * ($bracket_threshold[$bracket] / 100);

        return [
            'bracket_threshold' => $bracket_threshold[$bracket],
            'net_income' => $net_income,
            'threshold' => $threshold,
        ];
    }

    /**
     * RG20
     * User lost more than XXXX Euro in XX days
     * Values are configurable on DB (RG20-xxxx configs)
     * - default amount: 500000 cents
     * - default period: 30 days
     *
     * @param string $day
     */
    public function lostMoreThanXEuroInTheLastXDays(string $day): void
    {
        $trigger = "RG20";
        $days = $this->getAndCacheConfig('RG', 'RG20-days', 30);
        $amount = $this->getAndCacheConfig('RG', 'RG20-amount', 500000);
        $countries = phive('Config')->valAsArray('RG', 'RG20-countries');

        $countries_where = phive('SQL')->makeIn($countries);
        // Avoid SQL error
        if(empty($countries_where)) {
            return;
        }

        $sql = "SELECT
                    u.id,
                    COUNT(tl.id) as trigger_cnt,
                    ROUND((COALESCE(deposits.sum, 0) - COALESCE(withdrawals.sum, 0)) / currencies.mod) AS net_deposit
                FROM users u
                LEFT JOIN currencies ON currencies.code = u.currency
                LEFT JOIN triggers_log tl ON tl.user_id = u.id
                    AND tl.trigger_name = 'RG20'
                    AND tl.created_at > ('$day 23:59:59' - interval $days day)
                LEFT JOIN (
                    SELECT IFNULL(SUM(amount), 0) as sum, user_id
                           FROM deposits
                           WHERE timestamp > ('$day' - interval $days day)
                           AND status != 'disapproved'
                           GROUP BY user_id
                ) as deposits ON deposits.user_id = u.id
                LEFT JOIN (
                    SELECT IFNULL(SUM(amount), 0) as sum, user_id
                           FROM pending_withdrawals
                           WHERE timestamp > ('$day' - interval $days day)
                           AND status != 'disapproved'
                           GROUP BY user_id
                ) as withdrawals ON withdrawals.user_id = u.id
                WHERE u.country IN ($countries_where)
                GROUP BY u.id
                HAVING net_deposit >= $amount AND trigger_cnt = 0;";

        $users = $this->replica->shs()->loadArray($sql);

        $no_cents = round($amount / 100, 2);

        foreach ($users as $user) {
            $this->uh->logTrigger(
                $user['id'],
                $trigger,
                "User Net deposit more than EUR $no_cents in $days days",
                true,
                false,
                '',
                json_encode(['net_deposit' => (int)$user['net_deposit'] / 100])
            );
        }
    }

    /**
     * RG20 (for the Sportsbook)
     * User lost more than XXXX Euro in XX days
     * Values are configurable on DB (RG20-xxxx configs)
     * - default amount: 500000 cents
     * - default period: 30 days
     *
     * @param string $day
     * @return void
     */
    public function lostMoreThanXEuroInTheLastXDaysAtTheSportsbook(string $day): void
    {
        $trigger = "RG20";
        $days = $this->getAndCacheConfig('RG', 'RG20-days', 30);
        $amount = $this->getAndCacheConfig('RG', 'RG20-amount', 500000);
        $allowed_jurisdictions = phive('Config')->valAsArray('RG', 'RG20-sportsbook-jurisdictions');
        $allowed_brands = phive('Config')->valAsArray('RG', 'RG20-sportsbook-brands');

        if (! in_array(phive('BrandedConfig')->getBrand(), $allowed_brands)) {
            return;
        }

        $sql = "
            SELECT
              st.user_id,
              COUNT(tl.id) as trigger_cnt,
              SUM(IF(st.bet_type = 'bet', amount, 0) - IF(st.bet_type = 'win' AND st.result = 1, amount, 0)) / currencies.multiplier AS total_lost
            FROM
              sport_transactions st
              INNER JOIN users ON users.id = st.user_id
              LEFT JOIN currencies ON currencies.code = st.currency
              LEFT JOIN triggers_log tl ON tl.user_id = st.user_id
              AND tl.trigger_name = 'RG20'
              AND tl.created_at > (
                '$day 23:59:59' - interval $days day
              )
            WHERE (
                (
                  st.bet_type = 'win'
                  AND st.result = 1
                )
                OR st.bet_type = 'bet'
            ) AND st.created_at > ('$day' - interval $days day)
            GROUP BY
              st.user_id
            HAVING
              total_lost > $amount AND trigger_cnt = 0";

        $users = $this->replica->shs()->loadArray($sql);
        $no_cents = round($amount / 100, 2);

        foreach ($users as $user_data) {
            $user = cu($user_data['user_id']);

            if (! in_array($user->getJurisdiction(), $allowed_jurisdictions)) {
                continue;
            }

            $this->uh->logTrigger(
                $user_data['user_id'],
                $trigger,
                "User lost more than EUR $no_cents in $days days at the Sportsbook",
                true,
                false,
                $user_data['trigger_cnt'],
                json_encode(['total_lost' => $user_data['total_lost']])
            );
        }
    }

    /**
     * RG21 (and potentially RG36 via the lic() logic)
     *
     * Runs whenever a limit is increased or removed. Currently only on loss limit increases or removals but could change in the future.
     * Triggers only once a day
     *
     * About RG36:
     * 10k+ deposit for Swedes which should result in us contacting the player, but in general this should be the "contact player" flag for various reasons.
     *
     * The RG team will have to understand what should be done based on jurisdiction.
     *
     * @param DBUser $u_obj The user object.
     * @param int $new_amount The RG limit amount.
     * @param array $rgl The RG limit. // note: $rgl is a string representing type for $action==remove
     * @param string $action The RG limit type.
     *
     * @return void
     */
    public function onRgLimitChange($u_obj, $new_amount, $rgl, $action = 'change')
    {
        $trigger = 'RG21';
        $type = !is_array($rgl) ? $rgl : $rgl['type'];

        if($action == 'remove') {
            $description = "Removed $type limit";
        } else {
            if ($new_amount > $rgl['cur_lim']) {
                $description = "Increased $type limit";
            } else {
                return;
            }
        }

        // This one might result in RG36 being set.
        lic('onChangeRgLimit', [$u_obj, $rgl, $new_amount, $action], $u_obj);

        if (! in_array($type, ['loss', 'loss-sportsbook'])) {
            return;
        }

        if (!in_array($u_obj->getCountry(), phive('Config')->valAsArray('RG', "$trigger-countries"))) {
            return;
        }

        $res = $this->uh->logTrigger($u_obj, $trigger, $description);

        if (!empty($res)) {
            $this->sendEmailAndComment($u_obj, $trigger);
        }
    }

    public function onRgLimitAdd($u_obj, $clean_limit, $rgl){
        lic('onAddRgLimit', [$u_obj, $rgl, $clean_limit], $u_obj);
    }

    /**
     *
     * RG22 old AML17 old RG4
     * match on surname, same day and month of birth or Year and Month of birth, address
     *
     * @param DBUser $user
     * @return bool
     */
    public function otherLinks($user)
    {
        $trigger = 'RG22';
        $query_builder = function ($user, $period) {
            $dob   = $user->getAttribute('dob');
            $day   = date('d', strtotime($dob));
            $month = date('m', strtotime($dob));
            $year  = date('Y', strtotime($dob));
            $city = $this->replica->escape($user->getAttribute('city'), false);
            $address = $this->replica->escape($user->getAttribute('address'), false);
            $lastname = $this->replica->escape($user->getAttribute('lastname'), false);
            $zipcode = $this->replica->escape($user->getAttribute('zipcode'), false);
            $user_id = $user->getId();
            $where = ($period === 'day') ? "DAY(dob) = '{$day}'" : "YEAR(dob) = '{$year}'";

            return "SELECT id FROM users
                        WHERE lastname = '{$lastname}'
                        AND (
                            address = '{$address}'
                            OR zipcode = '{$zipcode}'
                            OR city = '{$city}'
                            )
                        AND $where
                        AND MONTH(dob) = '{$month}'
                        AND id != '$user_id'";
        };
        // Look for users with the same lastname AND day of birth AND month of birth
        $result_1 = $this->replica->shs('merge', '', null, 'users')->loadArray($query_builder($user, 'day'));
        // Look for users with the same address AND year of birth AND month of birth
        $result_2 = $this->replica->shs('merge', '', null, 'users')->loadArray($query_builder($user, 'year'));

        if (!empty($result_1) || !empty($result_2)) {
            $username = [];
            foreach ($result_1 as $r1) {
                $username[] = "u_" . cu($r1['id'])->getUsername() . "_u";
            }
            foreach ($result_2 as $r2) {
                $username[] = "u_" . cu($r2['id'])->getUsername() . "_u";
            }
            $username = array_unique($username);
            $username_list = implode(" ", $username);
            $this->uh->logTrigger($user, $trigger, $username_list);
            return true;
        }

        return false;
    }

    /**
     * RG23 old AML19
     *
     * We check that the ip or fingerprint of the current session does not match that of a closed account
     *
     * @param DBUser $user
     */
    public function activeLinkedClosedAccounts($user)
    {
        $trigger = "RG23";
        $uid = $user->userId;
        $current_session = $user->getCurrentSession();
        $ip = $current_session['ip'];

        // We check that the ip or fingerprint of the current session does not match that of a closed account
        $cur_fingerprint = $current_session['fingerprint'];
        $results = phive('SQL')
            ->shs()
            ->loadArray("
                SELECT DISTINCT(username) FROM users_sessions
                INNER JOIN users ON users.id = users_sessions.user_id
                WHERE users.active = 0
                AND ((fingerprint = '{$cur_fingerprint}' AND fingerprint != '') AND (ip = '{$ip}' AND ip != ''))
            ");

        if (!empty($results)) {

            $aUsername = [];
            foreach ($results as $r) {
                if ($r['username'] != $user->getUsername()) {
                    $aUsername[] = "u_" . $r['username'] . "_u";
                }
            }

            sort($aUsername);

            if (!empty($aUsername)) {
                $usernames = implode(" ", $aUsername);
                $extra = json_encode($aUsername);
                $desc = "{$user->getUsername()}, has ip:{$current_session['ip']} and fingerprint:{$current_session['fingerprint']} of closed accounts:{$usernames}";
                $md5 = md5($cur_fingerprint . $extra);
                $sql = "SELECT  id FROM triggers_log WHERE trigger_name ='$trigger' AND user_id = $uid AND txt = '$md5' LIMIT 1";
                $already_trigger = phive('SQL')->sh($uid)->loadAssoc($sql);
                if (empty($already_trigger)) {
                    $this->uh->logTrigger($uid, $trigger, $desc, true, false, '', $extra, $md5);
                }
            }
        }
    }

    /**
     * RG24 old AML23
     *
     * Declined deposit attempts exceeding 3 cards (card switch after 2 declined attempts)
     *
     * @param DBUser $user
     */
    public function declinedDeposits($user) {
        $uid = $user->userId;
        $trigger = 'RG24';

        if ($this->uh->hasTriggeredLastPeriod($uid, $trigger, 7)) {
            return;
        }

        $last_x_failed_deposits = $this->getAndCacheConfig('RG', "$trigger-failed-deposits-number", 3);
        $failed_attempts_threshold = $this->getAndCacheConfig('RG', "$trigger-failed-attempts-thold", 2);
        $different_cards_threshold = $this->getAndCacheConfig('RG', "$trigger-different-cards-thold", 2);

        try {
            $failed_deposits = phive('Cashier/Mts')->arf('getLastXFailedDeposits', [$uid, $last_x_failed_deposits, true]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            phive('Logger')->error($trigger . '_declinedDeposits', "Unable to connect with MTS/getLastXFailedDeposits for fetching failed deposits: {$e->getMessage()}");
            return;
        }

        // we need at least $different_cards_threshold+1 failed deposits
        if(count($failed_deposits) <= $different_cards_threshold) {
            return;
        }

        // group failed deposits by card
        $deposits_per_card = [];
        foreach($failed_deposits as $failed_deposit) {
            if(!isset($deposits_per_card[$failed_deposit['card_id']])) {
                $deposits_per_card[$failed_deposit['card_id']] = [];
            }
            $deposits_per_card[$failed_deposit['card_id']][] = $failed_deposit;
        }

        // We need at least $different_cards_threshold different cards
        if(count($deposits_per_card) < $different_cards_threshold) {
            return;
        }

        foreach($deposits_per_card as $card_id=>$card_deposits) {
            if(count($card_deposits) >= $failed_attempts_threshold) {
                // All cards except current
                $card_numbers = implode(', ', array_diff(array_keys($deposits_per_card), [$card_id]));
                $this->uh->logTrigger($user, $trigger, "2 or more failed attempts on card {$card_id}, before using Cards: {$card_numbers}.");
                return;
            }
        }
    }

    /**
     * @param $user_id
     * @param $start
     * @param $end
     * @return mixed
     */
    private function getBetCountInPeriod($user_id, $start, $end) {
        return $this->replica->sh($user_id, '', 'bets')->getValue("
            SELECT count(*) FROM bets
            WHERE user_id = {$user_id}
            AND created_at BETWEEN '{$start}' AND '{$end}'
        ");
    }

    /**
     * RG25 old AML28
     *
     * Velocity Deposits < or =  5 minutes apart or less between each failed deposit, no bets
     *
     * @param DBUser $user
     */
    public function velocityFailedDeposits($user) {
        $uid = $user->userId;
        $trigger = 'RG25';

        if ($this->uh->hasTriggeredLastPeriod($uid, $trigger, 7)) {
            return;
        }

        try {
            $failed_deposits = phive('Cashier/Mts')->arf('getLastXFailedDeposits', [$uid, 2]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            phive('Logger')->error($trigger . '_velocityFailedDeposits', "Unable to connect with MTS/getLastXFailedDeposits for fetching failed deposits: {$e->getMessage()}");
            return;
        }

        if(count($failed_deposits) <= 1) {
            return;
        }

        $last_deposit_time = $failed_deposits[0]['created_at'];
        $previous_deposit_time = $failed_deposits[1]['created_at'];

        $minutes_threshold = $this->getAndCacheConfig('RG', $trigger, 5);
        $minutes_between_deposits = phive()->subtractTimes(strtotime($last_deposit_time), strtotime($previous_deposit_time), 'm', 0, false);

        if($minutes_between_deposits <= $minutes_threshold) {
            if ($this->getBetCountInPeriod($uid, $previous_deposit_time, $last_deposit_time) == 0) {
                $this->uh->logTrigger($user, $trigger, "Less than {$minutes_threshold} ({$minutes_between_deposits}) minutes has passed between 2 consecutive failed deposits, without any bets.");
            }
        }
    }

    /**
     * Customers which current month loss is above a threshold needs to fill the SOWd
     *
     * @param DBUser $user
     */
    public function affordabilityCheck(DBUser $user)
    {
        $jurisdiction = $user->getJurisdiction();
        $user_currency = $user->getCurrency();
        $threshold  = phive('Config')->getValue('net-deposit-limit', "affordability-check-$jurisdiction-value");

        if (empty($threshold) || $user->hasSetting('source_of_funds_self_approval')) {
            return;
        }

        // Check if Dmapi service is available before proceeding
        $documents = phive('Dmapi')->getDocuments($user->getId());
        if (!phive('Dmapi')->isServiceAvailable($documents)) {
            phive('UserHandler')->logAction(
                $user,
                "SOWd affordability check skipped due to Dmapi service unavailability",
                'dmapi-service-unavailable'
            );
            return;
        }

        $doc = phive('Dmapi')->getDocumentByTag('sourceoffundspic', $user->getId());

        if ($doc !== false) {
            return;
        }

        $threshold = mc($threshold, $user);

        $current_month_loss = (float)realtimeStats()->getPlayerNgrInPeriod($user->getId());

        if ($current_month_loss > $threshold) {
            phive('UserHandler')->logAction(
                $user,
                "SOWd requested due to current month loss ". phive()->twoDec($current_month_loss) ." $user_currency over the ". phive()->twoDec($threshold) ." $user_currency threshold",
                'requested-sow'
            );
            phive('Dmapi')->createEmptyDocument($user->getId(), 'sourceoffunds', '', '', '', $user->getId());
            $user->setSetting('source_of_funds_status', 'requested');
            $user->setSetting('source_of_funds_activated', 1);
            $user->setSetting('source_of_funds_self_approval', 1);
        }
    }

    /**
     * Vulnerability checks on deposit.
     * RG70 RG71 & blocks & net deposit updates
     *
     * @param DBUser $user
     * @return void
     */
    public function vulnerabilityCheck(DBUser $user)
    {
        $user_id = $user->getId();
        list($vulnerable, $result) = lic('vulnerabilityCheck', [$user_id], $user);

        if (!$vulnerable) {
            return;
        }

        $flags = $result['flags'];
        $flags_string = $result['flags_string'];

        if (in_array('INDIVIDUAL_INSOLVENCY_REGISTER_MATCH', $flags)) {
            $trigger = 'RG70';
            $this->uh->logTrigger(
                $user,
                $trigger,
                "$trigger triggered for user with score VULNERABLE ({$flags_string})",
                true,
                true,
                '',
                $result['check_id']
            );
            lic('handleVulnerabilityCheckResult', [$user], $user);
        }

        if (in_array('JUDGMENTS_ORDERS_FINES_REGISTER_MATCH', $flags)) {
            $current_ndl = rgLimits()->getLimit($user, 'net_deposit', 'month')['cur_lim'];
            $whole_amount_current_ndl = nf2($current_ndl, true, 100);
            $users_currency = $user->getCurrency();

            $trigger = 'RG71';
            $this->uh->logTrigger(
                $user,
                $trigger,
                "Player in external registry - ({$flags_string})",
                true,
                true,
                '',
                $result['check_id']
            );

            $this->uh->logAction(
                $user,
                "beBettor Financial Check resulted in JUDGMENTS_ORDERS_FINES_REGISTER_MATCH. Current NDL is " .
                "$whole_amount_current_ndl $users_currency",
                "rg-actions"
            );
        }

        return;
    }

    /**
     * RG72
     * Flag if customer has a net-deposit of X amount or higher within 24 hours.
     *
     * @param DBUser $user
     *
     * @return void
     */
    public function hasXNetDepositInLastYHours(DBUser $user): void
    {
        $trigger = 'RG72';
        $user_id = $user->getId();

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, 7)) {
            return;
        }

        $user_currency = $user->getCurrency();
        $user_country = $user->getCountry();
        $hours = $this->getAndCacheConfig('RG', "$trigger-hours");
        if (empty($hours)) {
            return;
        }

        $net_deposit_configs = $this->getAndCacheConfig('RG', "$trigger-net-deposit");
        if (empty($net_deposit_configs)) {
            return;
        }

        foreach ($net_deposit_configs as $net_deposit_thold => $countries) {
            if (!in_array($user_country, $countries, true)) {
                continue;
            }

            $deposits = phive('SQL')->sh($user_id)
                ->getValue(
                    "SELECT IFNULL(SUM(amount), 0) as sum
                    FROM deposits
                    WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$hours} HOUR
                    AND status != 'disapproved'
                    AND user_id = {$user_id}"
                );

            $withdrawals = phive('SQL')->sh($user_id)
                ->getValue(
                    "SELECT IFNULL(SUM(amount), 0) as sum
                    FROM pending_withdrawals
                    WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$hours} HOUR
                    AND status != 'disapproved'
                    AND user_id = {$user_id}"
                );

            $user_net_deposit = (int) $deposits - (int) $withdrawals;
            $net_deposit_thold_converted = mc($net_deposit_thold, $user);

            if ($user_net_deposit >= $net_deposit_thold_converted) {
                $net_deposit_thold = phive('Currencer')->formatCurrency($net_deposit_thold, $user_currency);
                $user_net_deposit = phive('Currencer')->formatCurrency($user_net_deposit, $user_currency);
                $deposits = phive('Currencer')->formatCurrency($deposits, $user_currency);

                $description = "Customer has net-deposit of {$net_deposit_thold} or higher within {$hours} hours. (Customer net-deposit is {$user_net_deposit})";
                $data = json_encode([
                    'net_deposit_amount' => $user_net_deposit,
                    'deposit_amount' => $deposits,
                    'time' => $hours
                ], JSON_THROW_ON_ERROR);
                $this->uh->logTrigger($user, $trigger, $description, true, false, '', $data);
            }
        }
    }

    /**
     * RG74
     * Flag if customer has played X spins during the last y hours.
     *
     * @param DBUser $user
     *
     * @return void
     */
    public function hasPlayedXSpinsInLastYHours(DBUser $user): void
    {
        $trigger = 'RG74';
        $user_id = $user->getId();

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, 7)) {
            return;
        }

        $jurisdiction = $user->getJurisdiction();

        $hours_config = $this->getAndCacheConfig('RG', "$trigger-duration");
        if (empty($hours_config[$jurisdiction])) {
            return;
        }

        $spins_thold_config = $this->getAndCacheConfig('RG', "$trigger-spins");
        if (empty($spins_thold_config[$jurisdiction])) {
            return;
        }

        $hours = (int)$hours_config[$jurisdiction];
        $spins_cnt = (int)$this->spinsInLastYHours($user_id, $hours);
        if ($spins_cnt >= (int)$spins_thold_config[$jurisdiction]) {
            $data = json_encode([
                'amount_of_spins' => $spins_cnt,
                'hours_period' => $hours
            ], JSON_THROW_ON_ERROR);
            $this->uh->logTrigger($user, $trigger, "Customer played {$spins_cnt} spins last {$hours} hours",
                true, false, '', $data);
        }
    }

    /**
     * Check number of spins played by user in last Y hours
     *
     * @param int $user_id
     * @param int $hours
     * @return string
     */
    public function spinsInLastYHours(int $user_id, int $hours = 0): string
    {
        return phive('SQL')->sh($user_id)
            ->getValue(
                "SELECT
                    count(*)
                FROM
                    bets b
                WHERE
                    b.user_id = {$user_id}
                    AND b.created_at > CURRENT_TIMESTAMP() - INTERVAL {$hours} HOUR");
    }


    /**
     * RG75
     * Triggers when customers wager a certain amount in the last Y hours
     *
     * @return void
     */
    public function triggerUsersWageringInLastYHours(): void
    {
        $trigger = 'RG75';

        $hours_config = $this->getAndCacheConfig('RG', "{$trigger}-duration");
        $wager_threshold_config = $this->getAndCacheConfig('RG', "{$trigger}-wager");

        if (empty($hours_config) || empty($wager_threshold_config)) {
            return;
        }

        foreach ($hours_config as $jurisdiction => $hours) {
            $threshold = (int)$wager_threshold_config[$jurisdiction];

            // Validate that both $hours and $threshold are greater than 0
            if ($hours <= 0 || $threshold <= 0) {
                continue;
            }

            $user_details_list = $this->getCustomersWageringInLastYHours($jurisdiction, (int)$hours, $threshold);

            foreach ($user_details_list as $user_details) {
                if ($this->uh->hasTriggeredLastPeriod($user_details['user_id'], $trigger, 7)) {
                    continue;
                }

                $user = cu($user_details['user_id']);
                $total_wagered = $user_details['total_wagered'];
                $currency_symbol = $user_details['symbol'];

                $currency = $user->getCurrency();

                // Convert total wagered from EUR to the user's currency
                $converted_total_wager = rnfCents(mc($total_wagered, $currency));

                $total_wager_with_currency = $currency_symbol . $converted_total_wager;

                $this->uh->logTrigger(
                    $user,
                    $trigger,
                    "Customer wagered {$total_wager_with_currency} in the last {$hours} hours",
                    true,
                    true,
                    '',
                    json_encode([
                                    'total_wager' => $total_wager_with_currency,
                                    'duration' => $hours
                                ])
                );
            }
        }
    }

    /**
     * Retrieves customers who wagered in the last Y hours.
     *
     * @param string $jurisdiction
     * @param int $hours
     * @param int $threshold
     * @return array
     */
    public function getCustomersWageringInLastYHours(string $jurisdiction, int $hours, int $threshold): array
    {
        $query = "
                SELECT
                    b.user_id,
                    COALESCE(SUM(b.amount / c.mod), 0) as total_wagered,
                    c.symbol
                FROM
                    bets b
                INNER JOIN
                    users u ON u.id = b.user_id
                INNER JOIN
                    users_settings us ON (u.id = us.user_id AND us.setting = 'jurisdiction')
                INNER JOIN
                    currencies c ON b.currency = c.code
                WHERE
                    us.value = '{$jurisdiction}'
                    AND b.created_at > CURRENT_TIMESTAMP() - INTERVAL {$hours} HOUR
                GROUP BY
                    b.user_id
                HAVING
                    total_wagered >= {$threshold}
        ";
        return phive('SQL')->shs()->loadArray($query);
    }

    /**
     *  RG76
     *
     *  Customer had a big win of X their bet
     *
     * @param int $user_id
     * @param int $bet_amount
     * @param int $win_amount
     * @return void
     */
    public function hadBigWinOfXTheirBet(int $user_id, int $bet_amount, int $win_amount): void
    {
        $trigger = 'RG76';
        $min_multipliers = $this->getAndCacheConfig('RG', "{$trigger}-min-multiplier");
        $max_multipliers = $this->getAndCacheConfig('RG', "{$trigger}-max-multiplier");

        if((int)array_sum((array)$min_multipliers) === 0){
            return;
        }

        if((int)array_sum((array)$max_multipliers) === 0){
            return;
        }

        $user = cu($user_id);
        $jurisdiction = $user->getJurisdiction();

        if(empty($min_multipliers[$jurisdiction]) || empty($max_multipliers[$jurisdiction])){
            return;
        }

        $min_multiplier = (int)$min_multipliers[$jurisdiction];
        $max_multiplier = (int)$max_multipliers[$jurisdiction];

        $win_multiplier = (int)floor($win_amount / $bet_amount);
        if ($win_multiplier >= $min_multiplier && $win_multiplier <= $max_multiplier) {
            $data = json_encode([
                'multiplier' => $min_multiplier,
            ], JSON_THROW_ON_ERROR);
            $this->uh->logTrigger($user, $trigger, "Customer had a big win of {$min_multiplier}x their bet.",
                true, false, '', $data);
        }
    }


    /**
     * RG27
     * Deposit from a player with high RG Risk Profile Rating.
     * Only flag customers with RG Risk Profile rating between x - y in score.
     *
     * @param DBUser $user
     * @return bool
     */
    public function highRiskDepositor($user)
    {
        $trigger = 'RG27';

        if ($this->uh->hasTriggeredLastPeriod($user->getId(), $trigger, 7)) {
            return false;
        }

        $rg_score = $this->getLatestRatingScore($user->getId(), 'RG', 'tag');

        if (!empty($rg_score)) {

            if (!$this->isUserScoreInTriggerRange('RG', $user->getId(), $trigger)) {
                return false;
            }

            $this->uh->logTrigger($user, $trigger, "$trigger triggered for user with score {$rg_score}");

            return true;
        }

        return false;
    }

    /**
     * RG26
     *
     * Player have cancelled X amount of withdrawal transactions on current day.
     * Only flag customers with RG Risk Profile rating between x - y in score.
     *
     * @param DBUser $user
     * @return bool
     */
    public function canceledXTransactionsToday($user) {
        $trigger = 'RG26';
        $user_id = $user->getId();
        $transactions_number = (int) $this->getAndCacheConfig('RG', "$trigger-transactions-number", 3);

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return false;
        }

        $today = phive()->today();
        $number_of_canceled_withdrawals = (int) $this->getCancelledWithdrawals($user, "DATE(approved_at) = '{$today}'");

        if ($number_of_canceled_withdrawals !== $transactions_number) {
            return false;
        }

        $this->uh->logTrigger($user, $trigger, "Player has canceled $number_of_canceled_withdrawals withdrawal today: {$today}");
        return true;
    }


    /**
     * Performs RG checks on customers that had some action on the requested day Ex. bets, deposits...
     * Users are logged on users_daily_stats only if there was a variation.
     *
     * We perform 3 types of checks: (each one can fire 1 or more RG triggers)
     * - changes on wager pattern
     * - changes on deposit pattern
     * - changes on session duration
     *
     * Customers with score = 0 are not at risk, so we can skip further checks.
     * All above checks are fired only on customers with score inside a certain range (usually 50/80 or 80/100)
     *
     * @param string $day
     */
    public function getPlayersByDay(string $day): void
    {
        // we need to get the data from the shards cause "risk_profile_rating_log" is not an aggregated table on master
        // and this is much faster on performance than querying for the score on each user.
        // NOTE: we get the latest score available before "$day" so in case of re-checking past data we will get the right score.
        $sql = "
            SELECT
                uds.user_id,
                uds.bets,
                uds.deposits,
                IFNULL(latest_rprl_per_user.rating, 0) as score
            FROM
                users_realtime_stats uds
                LEFT JOIN (
                    select user_id, rating
                    FROM risk_profile_rating_log
                    WHERE id IN  (
                        SELECT MAX(id)
                        FROM risk_profile_rating_log
                        WHERE rating_type = 'RG' AND created_at <= '$day 23:59:59'
                        group by user_id
                    )
                ) latest_rprl_per_user ON latest_rprl_per_user.user_id = uds.user_id
            WHERE
                uds.date ='$day'
        ";
        $users = $this->replica->shs()->loadArray($sql);

        foreach ($users as $uds_data) {
            $score = (int)$uds_data['score'];
            $user_id = uid($uds_data['user_id']);
            if(empty($score)) {
                continue;
            }
            if ($uds_data['bets'] > 0) {
                $this->checkWagerPatternChangeDaily($user_id, $score, $day);
            }
            if ($uds_data['deposits'] > 0) {
                $this->changeDepositPattern($user_id, $score, $day);
            }
            $this->changeSessionTime($user_id, $score, $day);
        }
    }

    /**
     * RG28 - every bet - Compare lifetime average bet amount per spin/transaction to current day's last X spins and flag if average bet amount has increased with X%.
     * Change to last x spins and we store it in session
     * Only flag customers with RG Risk Profile rating between x - y in score.
     *
     * @param $user
     */
    public function lastXSpinsAgainstLifetimeAverageBetPerSpin($user)
    {
        $trigger = 'RG28';
        $user_id = $user->getId();

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return;
        }

        $num_of_bets = $this->getAndCacheConfig('RG', 'RG28-last_bets', 100);
        $percentage_threshold = $this->getAndCacheConfig('RG', 'RG28-percentage', 100);

        $last_x_bets = $this->getLastXBetsForUser($user_id, $num_of_bets);

        // we don't have enough bets to do the comparison
        if (count($last_x_bets) < $num_of_bets) {
            return;
        }

        $avg_last = phive()->sum2d($last_x_bets, 'amount') / $num_of_bets;
        $avg_total = $this->getLifetimeAverage($user_id)['bet_per_spin'];
        $change_in_percentage = $this->getChangeInPercentage($avg_last, $avg_total);

        if ($change_in_percentage > $percentage_threshold) {
            $change_in_percentage = nf2($change_in_percentage, true);
            $this->uh->logTrigger($user, $trigger, "Player increases average bet amount by {$change_in_percentage}% in the last  $num_of_bets spin compared to lifetime average bet amount per spin/transaction");
        }
    }

    /**
     * RG31 - every bet - Player increases their average bet on last X spins on the current day with X% compared to previous average bet on previous X spins on current day.
     * Only flag customers with RG Risk Profile rating between x - y in score.
     *
     * @param $user
     */
    public function lastXSpinsAgainstPreviousXSpins($user)
    {
        $trigger = 'RG31';
        $user_id = $user->getId();

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return;
        }

        $num_of_bets = $this->getAndCacheConfig('RG', 'RG31-last_bets', 100);
        $percentage_threshold = $this->getAndCacheConfig('RG', 'RG31-percentage', 100);

        $num_of_bets_with_previous = $num_of_bets * 2; // Ex if setting is 10 we want to grab 20 bets

        $last_x_bets_with_previous = $this->getLastXBetsForUser($user_id, $num_of_bets_with_previous);

        // we don't have enough bets to do the comparison
        if (count($last_x_bets_with_previous) < $num_of_bets_with_previous) {
            return;
        }
        $last_x_bets = array_slice($last_x_bets_with_previous, 0, $num_of_bets);
        $prev_x_bets = array_slice($last_x_bets_with_previous, $num_of_bets, $num_of_bets);

        $avg_last = phive()->sum2d($last_x_bets, 'amount') / $num_of_bets;
        $avg_previous = phive()->sum2d($prev_x_bets, 'amount') / $num_of_bets;
        $change_in_percentage = $this->getChangeInPercentage($avg_last, $avg_previous);

        if ($change_in_percentage > $percentage_threshold) {
            $change_in_percentage = nf2($change_in_percentage, true);
            $this->uh->logTrigger($user, $trigger, "Player increases the  bet amount by {$change_in_percentage}% in the last $num_of_bets spins compared to previous $num_of_bets spins ");
        }
    }

    /**
     * RG29 - every night - Compare lifetime average amount of bets/spins transactions per active day to current day and flag if bets/spins transactions sum has increased with X%.
     * Only flag customers with RG Risk Profile rating between x - y in score.
     *
     * @param int $user_id
     * @param int $score
     * @param string $day
     */
    public function todayAgainstLifetimeAverageBetPerSpin(int $user_id, int $score, string $day): void
    {
        $trigger = 'RG29';

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger, $score)) {
            return;
        }

        $percentage_threshold = $this->getAndCacheConfig('RG', 'RG29-percentage', 10);
        $average_bet_per_spin = $this->getLifetimeAverage($user_id, $day)['bet_per_spin'];
        $last_day_bet_per_spin = $this->getLastDayAverage($user_id, $day)['bet_per_spin'];

        $change_in_percentage = $this->getChangeInPercentage($last_day_bet_per_spin, $average_bet_per_spin);

        if ($change_in_percentage > $percentage_threshold) {
            $change_in_percentage = nf2($change_in_percentage, true);
            $this->uh->logTrigger($user_id, $trigger, "Player increases the daily average bet amount (bet/spin) by {$change_in_percentage}%");
        }
    }

    /**
     * RG30 - Compare lifetime average total wager amount per active day to current day and flag if wager amount has increased with X%.
     * Only flag customers with RG Risk Profile rating between x - y in score.
     *
     * @param int $user_id
     * @param int $score
     * @param string $day
     */
    public function todayAgainstLifetimeAverageDailyWager(int $user_id, int $score, string $day): void
    {
        $trigger = 'RG30';

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger, $score)) {
            return;
        }

        $percentage_threshold = $this->getAndCacheConfig('RG', 'RG30-percentage', 10);
        $average_daily_wager = $this->getLifetimeAverage($user_id, $day)['daily_wager'];
        $last_day_daily_wager = $this->getLastDayAverage($user_id, $day)['daily_wager'];

        $change_in_percentage = $this->getChangeInPercentage($last_day_daily_wager, $average_daily_wager);

        if ($change_in_percentage > $percentage_threshold) {
            $change_in_percentage = nf2($change_in_percentage, true);
            $this->uh->logTrigger($user_id, $trigger, "Player increases the daily bet amount (total) by {$change_in_percentage}%");
        }
    }

    /**
     * Changes on wager pattern
     * Check for RG29 & RG30
     *
     * @param int $user_id
     * @param int $score - latest RG cached score
     * @param string $day
     */
    public function checkWagerPatternChangeDaily(int $user_id, int $score, string $day): void
    {
        $this->todayAgainstLifetimeAverageBetPerSpin($user_id, $score, $day);
        $this->todayAgainstLifetimeAverageDailyWager($user_id, $score, $day);
    }

    /**
     * Check for changes in session duration, Ex. if user increase the average logged in time or plays more games.
     *
     * RG32 Compare lifetime average logged in session time per active day to current day and flag if session time has increased with X%.
     * RG33 Compare lifetime average unique game session time per active day to current day and flag if average unique game session time has increased with X%.
     * RG34 Compare lifetime average amount of unique game sessions per active day to current day and flag if amount of game sessions on current day has increased with X%.
     *
     * @param int $user_id
     * @param int $score
     * @param string $date
     * @return bool
     */
    public function changeSessionTime(int $user_id, int $score, string $date): bool
    {
        $triggers = ['RG32', 'RG33', 'RG34'];
        $percentage = [];
        // TODO see if I can refactor the below "isScoreIn + getConfig" in a common method... there are many similar calls /Paolo
        foreach ($triggers as $key => $trigger) {
            // if not in range or already triggered we skip the check.
            if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger, $score)) {
                unset($triggers[$key]);
            } else {
                $percentage[$trigger] = $this->getAndCacheConfig('RG', "{$trigger}-percentage", 10);
            }
        }

        if (empty($triggers)) {
            return false;
        }

        $trigger = 'RG32';
        if (array_search($trigger, $triggers) !== false) {
            $sql = "
                SELECT
                    DATE(ended_at) as date,
                    SUM(TIMESTAMPDIFF(SECOND, created_at, ended_at)) as session_duration
                FROM
                    users_sessions
                WHERE
                    user_id = {$user_id}
                    AND ended_at <= '{$date} 23:59:59'
                    AND ended_at != '0000-00-00 00:00:00'
                GROUP BY date(ended_at)
                ORDER BY id DESC
            ";
            $lifetime_sessions_duration = $this->replica->sh($user_id)->loadArray($sql);
            // we remove current day from the full list, and if no session today we skip the checks.
            $today_session = array_shift($lifetime_sessions_duration);
            if($today_session['date'] === $date) {
                $lifetime_avg_time = phive()->sum2d($lifetime_sessions_duration, 'session_duration') / count($lifetime_sessions_duration);
                $res = $this->getChangeInPercentage($today_session['session_duration'], $lifetime_avg_time);
                if ($res > $percentage[$trigger] && !$this->uh->hasTriggeredLastPeriod($user_id, $trigger, 1, 'HOUR')) {
                    $res = nf2($res, true);
                    $this->uh->logTrigger($user_id, $trigger, "Session time has increased by {$res}%");
                }
            }
            unset($triggers[0]);
        }

        if (empty($triggers)) {
            return false;
        }

        $sql = "
            SELECT
                SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS time_session,
                COUNT(id) AS n_times,
                date(end_time) AS day
            FROM
                users_game_sessions
            WHERE
                user_id = {$user_id}
                AND end_time <= '{$date} 23:59:59'
                AND end_time != '0000-00-00 00:00:00'
                AND bet_cnt > 0
            GROUP BY date(end_time)
            ORDER BY id DESC
        ";

        $lifetime_game_sessions_duration = $this->replica->sh($user_id)->loadArray($sql);
        $today = array_shift($lifetime_game_sessions_duration);
        // no game play today or not enough game sessions (Ex. first day)
        // TODO check if we should apply the below checks only after we are above a certain threshold
        //  (Ex. after we have at least 20 days worth of active gameplay, to avoid false positives) /Paolo
        if ($today['day'] !== $date || count($lifetime_game_sessions_duration) <= 1) {
            return false;
        }

        $trigger = 'RG33';
        if (array_search($trigger, $triggers) !== false) {
            $time_today = $today['time_session'] / $today['n_times'];
            $game_time = phive()->sum2d($lifetime_game_sessions_duration, 'time_session') / phive()->sum2d($lifetime_game_sessions_duration, 'n_times');
            $res = $this->getChangeInPercentage($time_today, $game_time);
            if ($res > $percentage[$trigger] && !$this->uh->hasTriggeredLastPeriod($user_id, $trigger, 1, 'HOUR')) {
                $res = nf2($res, true);
                $this->uh->logTrigger($user_id, $trigger, "Game session time has increased {$res}% ");
            }
        }

        $trigger = 'RG34';
        if (array_search($trigger, $triggers) !== false) {
            $amount_today = $today['n_times'];
            $number_of_game_session = phive()->sum2d($lifetime_game_sessions_duration, 'n_times') / count($lifetime_game_sessions_duration);
            $res = $this->getChangeInPercentage($amount_today, $number_of_game_session);
            if ($res > $percentage[$trigger] && !$this->uh->hasTriggeredLastPeriod($user_id, $trigger, 1, 'HOUR')) {
                $res = nf2($res, true);
                $this->uh->logTrigger($user_id, $trigger, "Number of game sessions has increased by {$res}% ");
            }
        }

        return true;
    }


    /*RG35  Flag if average bet on current game session have increased from previous game session with X%.*/
    public function averageBetIncreasedAgainstPreviousSession($user)
    {
        $trigger = 'RG35';
        $user_id = $user->getId();

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return false;
        }

        $percentage[$trigger] = $this->getAndCacheConfig('RG', "$trigger-percentage", 100);
        $sql = "SELECT bet_amount / bet_cnt AS avg_bet FROM users_game_sessions WHERE user_id = {$user_id} AND bet_cnt > 0 ORDER BY id DESC LIMIT 2";
        $last_sessions = phive('SQL')->sh($user_id)->loadArray($sql);

        if (count($last_sessions) < 2 || $last_sessions[1]['avg_bet'] == 0)
            return false;
        $res = $this->getChangeInPercentage($last_sessions[0]['avg_bet'], $last_sessions[1]['avg_bet']);

        if ($res > (int)$percentage[$trigger] && !$this->uh->hasTriggeredLastPeriod($user_id, $trigger, 1, 'HOUR')) {
            $res = nf2($res, true);
            $this->uh->logTrigger($user, $trigger, "The average bet in the last game session has increased by {$res}% ", true, false);
        }
    }

    /**
     * Wrapper for RG38 & RG39
     * TODO see if we need to make time interval configurable, as if it is different between the 2 trigger we need to
     *  rework the logic and fire 2 queries. ATM keeping it like this to avoid hitting DB twice. /Paolo
     *
     * @param DBUser $user
     * @param array $game_session - last closed game session
     */
    public function checkLossAmountBasedOnNGR(DBUser $user, array $game_session): void
    {
        $triggers_threshold = phive('SQL')->loadArray("SELECT name, ngr_threshold FROM triggers WHERE name IN ('RG38', 'RG39')");
        $days = 30;
        $user_id = $user->getId();
        $start_time = phive()->modDate($game_session['end_time'], "-$days days");
        $end_time = $game_session['end_time'];

        $query = "SELECT
                    u.id,
                    deposits.sum as total_deposits,
                    withdrawals.sum as total_withdrawals,
                    (IFNULL(deposits.sum, 0) - IFNULL(withdrawals.sum, 0)) as net_deposit
                FROM users u
                LEFT JOIN (
                    SELECT IFNULL(SUM(amount), 0) as sum, user_id
                           FROM deposits
                           WHERE timestamp BETWEEN '{$start_time}' AND '{$end_time}'
                           AND status != 'disapproved'
                           GROUP BY user_id
                ) as deposits ON deposits.user_id = u.id
                LEFT JOIN (
                    SELECT IFNULL(SUM(amount), 0) as sum, user_id
                           FROM pending_withdrawals
                           WHERE timestamp BETWEEN '{$start_time}' AND '{$end_time}'
                           AND status != 'disapproved'
                           GROUP BY user_id
                ) as withdrawals ON withdrawals.user_id = u.id
                WHERE u.id = {$user_id};";
        $net_deposit = phive('SQL')->sh($user_id)->loadArray($query);
        $net_deposit_in_euro = mc($net_deposit[0]['net_deposit'] ?? 0, $user, '');

        foreach($triggers_threshold as $trigger) {
            if($net_deposit_in_euro > $trigger['ngr_threshold']) {
                // if already triggered in the last $days days we don't re-trigger.
                if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger['name'], $days)) {
                    continue;
                }
                $formatted_threshold = nfCents($trigger['ngr_threshold'], true);
                $formatted_net_deposit_in_euro = nfCents($net_deposit_in_euro, true);
                $this->uh->logTrigger(
                    $user->getId(),
                    $trigger['name'],
                    "User Net Deposit more than EUR {$formatted_threshold} (EUR {$formatted_net_deposit_in_euro}) in $days days"
                );
            }
        }
    }

    /**
     * RG58 - Game Session Duration
     * A flag alerting to a session duration of more than X hours on Y consecutive days.
     * Example: if a player has logged in for 65 minutes and played 3 sequential games of 20m each,
     *  then 60 minutes have elapsed and the flag will be triggered
     *
     * @param DBUser $user
     * @return bool
     */
    public function gameSessionDuration($user)
    {
        if (empty($user)) {
            return false;
        }

        $trigger = 'RG58';
        $user_id = $user->getId();

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, 7)) {
            return false;
        }

        if (!$this->isUserScoreInTriggerRange('RG', $user_id, $trigger)) {
            return false;
        }

        $play_hours = (int)$this->getAndCacheConfig('RG', "$trigger-gameplay-hours", 1);
        $consecutive_days = (int)$this->getAndCacheConfig('RG', "$trigger-consecutive-days", 2);

        // If any of the values is set to 0 on the DB the flag needs to be considered disabled.
        if(empty($play_hours) || empty($consecutive_days)) {
            return false;
        }

        $sql = "
            SELECT DATE(start_time) AS date
            FROM users_game_sessions
            WHERE user_id = {$user_id}
                AND start_time >= (CURDATE() - INTERVAL {$consecutive_days} DAY)
            GROUP BY session_id, DATE(start_time)
            HAVING SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) >= {$play_hours}
        ";

        $days = phive('SQL')->sh($user_id)->loadArray($sql);
        $days = array_unique(array_column($days, 'date'));

        // Not enough days in a row
        if (count($days) < $consecutive_days) {
            return false;
        }

        // double check trigger log due to duplication issue
        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, 1, 'HOUR')) {
            return false;
        }
        $data = json_encode([
            'hours' => $play_hours,
        ], JSON_THROW_ON_ERROR);
        return $this->uh->logTrigger(
            $user,
            $trigger,
            "User played more than {$play_hours} hours for {$consecutive_days} consecutive days",
            true,
            false,
            '',
            $data
        );
    }

    /**
     * RG40 - For when customers go over their selected amounts in a monthly basis.
     * @param string $day
     */
    public function intendedGambling(string $day): void
    {
        $trigger = 'RG40';
        $sql = "SELECT user_id, total, val FROM (
                    SELECT SUM(uds.bets) AS total, uds.user_id, us.value AS val
                    FROM users_realtime_stats AS uds
                           RIGHT JOIN users_settings AS us
                              ON us.user_id = uds.user_id AND setting = 'intended_gambling' AND value != ''
                    WHERE MONTH(uds.date) = MONTH('$day')
                      AND YEAR(uds.date) = YEAR('$day')
                    GROUP BY uds.user_id
                ) AS aux
                WHERE aux.total/100 > SUBSTRING_INDEX(val, '-', -1);";

        $users = $this->replica->shs()->loadArray($sql);

        foreach ($users as $user) {
            $this->uh->logTrigger($user['user_id'], $trigger, "Player exceeded the intended gambling.", false);
        }
    }

    /**
     * RG59 - To flag customer that play between certain times. For example, a customer placed X amount in wagers like
     *          from 23 - 07 for a specific country.
     *
     * @param DBUser $user
     * @param array $game_session
     * @return mixed
     * @throws Exception
     */
    public function gamePlayTime(DBUser $user, array $game_session): bool
    {
        $trigger = 'RG59';

        $start_hour = (int)$this->getAndCacheConfig('RG', "$trigger-start-hour", 23);
        $end_hour = (int)$this->getAndCacheConfig('RG', "$trigger-end-hour", 7);
        $wager_limit = (int)$this->getAndCacheConfig('RG', "$trigger-wager-eur-cents", 1000000);
        $country_list = $this->getAndCacheConfig('RG', "$trigger-countries");

        if (is_string($country_list) && !empty($country_list) && strpos($country_list, $user->getCountry()) === false) {
            return false;
        }

        if (is_array($country_list) && !empty($country_list) && !in_array($user->getCountry(), $country_list)) {
            return false;
        }

        if (empty($game_session) || (int)chg($user, $this->def_cur, $game_session['bet_amount']) < $wager_limit) {
            return false;
        }

        $local_timezone = new DateTimeZone(phive('DBUserHandler')->getUserLocalTimezone($user));
        $session_start = (new DateTime($game_session['start_time']))->setTimezone($local_timezone);

        if (phive()->isDateTimeBetweenHours($session_start, $start_hour, $end_hour)) {

            $description = "Started a game session at {$session_start->format('H:i:s')} customer local time and wagered " . round($game_session['bet_amount'] / 100) . " {$user->getCurrency()}";

            return $this->uh->logTrigger($user, $trigger, $description);
        }

        return false;
    }

    /**
     * Game sessions between X and Y (1AM-5AM) time interval with wagering over Z (10,000€)
     * Accumulation of wagers to 10k during the period (1AM - 5AM) – could be 1 bet of 10k, or 10 bets of 1k
     *
     * @param DBUser $user
     * @return bool
     * @throws Exception
     */
    public function sportsbookGameplayWagerOverTimeInterval(DBUser $user): bool
    {
        $trigger = 'RG59';
        $start_hour = (int)$this->getAndCacheConfig('RG', "$trigger-start-hour", 1);
        $end_hour = (int)$this->getAndCacheConfig('RG', "$trigger-end-hour", 5);
        $wager_threshold = (int)$this->getAndCacheConfig('RG', "$trigger-wager-eur-cents", 1000000);
        $user_timezone = new DateTimeZone(phive('DBUserHandler')->getUserLocalTimezone($user));
        $start_time = (new DateTime('NOW', $user_timezone))
            ->setTime($start_hour, 0)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H-i-s');
        $end_time = (new DateTime('NOW', $user_timezone))
            ->setTime($end_hour, 0)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H-i-s');

        $query = "
            SELECT sum(sp.amount) as 'total'
            FROM sport_transactions sp
            WHERE bet_type = 'bet'
            AND created_at BETWEEN '$start_time' AND '$end_time'";
        $bets_amount = phive('SQL')->sh($user->getId())->loadObject($query);
        $total = $bets_amount->total ?? 0;

        if ( (int)chg($user, $this->def_cur, $total) >= $wager_threshold) {
            $description = "Sports betting sessions between time interval $start_time - $end_time (UTC) with wagering over (" . $wager_threshold / 100 . "€)
            Current wager amount:" . round($total / 100) . " {$user->getCurrency()}";
            return (bool) $this->uh->logTrigger($user, $trigger, $description);
        }

        return false;
    }

    /**
     * RG64 High Deposit Frequency Within 24 hours
     *
     * @param DBUser $user
     * @return bool
     */
    public function highDepositFrequency(DBUser $user): bool
    {
        $user_id = $user->getId();
        $trigger_name = 'RG64';

        $is_config_on = $this->isChoiceConfigTurnedOn('RG', "{$trigger_name}-high-deposit-frequency", 'off');
        $flag_triggered_last_period = $this->uh->hasTriggeredLastPeriod($user_id, $trigger_name, 7);
        if (!$is_config_on || $flag_triggered_last_period) {
            return false;
        }

        $start_time = phive()->hisMod('-1 day');
        $nr_of_deposits_last_24h = phive('Cashier')->getDepositCount(
            $user_id,
            '',
            " AND `timestamp` >= '{$start_time}'"
        );

        $threshold = $this->getAndCacheConfig('RG', "{$trigger_name}-high-deposit-frequency-threshold", 0);
        if ($nr_of_deposits_last_24h > $threshold) {
            return (bool) $this->uh->logTrigger($user, $trigger_name, "High number of deposits in 24 hours");
        }

        return false;
    }

    /**
     * RG65
     * Mark users that met the condition:
     * users younger/older than age N, haven't reached the net loss thold for X consequence weeks
     * net loss = (starting balance + deposits - withdrawals - end balance)
     *
     * @param string      $country
     * @param int         $user_age
     * @param string      $age_operator
     * @param int         $consecutive_weeks
     * @param int         $net_loss_thold
     * @param string      $date now|Y-m-d
     *
     * @return array
     * @throws Exception
     */
    public function checkIntensiveGamblerSignsByAccumulatedNetLoss(
        string $country,
        int $user_age,
        string $age_operator,
        int $consecutive_weeks,
        int $net_loss_thold,
        string $date = 'now'
    ): array
    {
        $trigger_name = 'RG65';
        $net_loss_operator = '>=';
        [
            $select,
            $join_weeks,
            $where_net_loss_per_week_statement,
        ] = $this->intensiveGamblerQueryComponentsSupplier(
            $date,
            $consecutive_weeks,
            $net_loss_thold,
            $net_loss_operator
        );

        $query = "SELECT
                    {$select}
                FROM users u
                {$join_weeks}
                LEFT JOIN(
                    SELECT tl.trigger_name, tl.user_id as user_id
                    FROM triggers_log tl WHERE tl.trigger_name = '{$trigger_name}') tl ON tl.user_id = u.id
                WHERE tl.trigger_name IS NULL
                AND CAST(DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), dob)), '%Y') AS int) {$age_operator} '{$user_age}'
                AND u.country = '{$country}'
                AND u.id NOT IN (
                    SELECT user_id FROM users_settings AS u_s
                    WHERE (u_s.setting = 'registration_in_progress' AND u_s.value >= 1)
                    OR (u_s.setting = 'test_account' AND u_s.value = 1)
                )
                {$where_net_loss_per_week_statement}
                GROUP BY u.id;";

        /**
         * @var array{"user_id": int} $results
         */
        $results = phive('SQL')->shs()->loadArray($query);
        foreach ($results as $result) {
            $user = cu($result['user_id']);
            $this->uh->logTrigger(
                $user,
                $trigger_name,
                "Intensive Gambler detected.
                User has accumulated Net Loss more than {$net_loss_thold} {$consecutive_weeks} consecutive weeks");
        }

        return $results;
    }
    /**
     * Log action with tag `tis-flag-revoked` & revoke RG65 by the condition:
     * users younger/older than age N haven't reached the net loss thold for X consequence weeks
     * net loss = starting balance + deposits - withdrawals - end balance
     *
     *
     * @param string      $country
     * @param int         $user_age
     * @param string      $age_operator
     * @param int         $consecutive_weeks
     * @param int         $net_loss_thold
     * @param string      $date now|Y-m-d
     *
     * @return array
     * @throws Exception
     */
    public function revocationOfIntensiveGamblerSignsByAccumulatedNetLoss(
        string $country,
        int $user_age,
        string $age_operator,
        int $consecutive_weeks,
        int $net_loss_thold,
        string $date = "now"
    ): array
    {
        $trigger_name = 'RG65';
        $net_loss_operator = '<';
        [
            $select,
            $join_weeks,
            $where_net_loss_per_week_statement,
        ] = $this->intensiveGamblerQueryComponentsSupplier(
            $date,
            $consecutive_weeks,
            $net_loss_thold,
            $net_loss_operator
        );
        $query = "SELECT
                    {$select}
                FROM users u
                {$join_weeks}
                LEFT JOIN(
                    SELECT tl.trigger_name, tl.user_id as user_id
                    FROM triggers_log tl WHERE tl.trigger_name = '{$trigger_name}') tl ON tl.user_id = u.id
                WHERE tl.trigger_name IS NOT NULL
                AND CAST(DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), dob)), '%Y') AS int) {$age_operator} '{$user_age}'
                AND u.country = '{$country}'
                AND u.id NOT IN (
                    SELECT user_id FROM users_settings AS u_s
                    WHERE u_s.setting = 'registration_in_progress' AND u_s.value >= 1
                )
                {$where_net_loss_per_week_statement}
                GROUP BY u.id";

        /**
         * @var array{"user_id": int} $results
         */
        $results = phive('SQL')->shs()->loadArray($query);
        foreach ($results as $result) {
            $user = cu($result['user_id']);
            if ($this->uh->getBlockReason($user->getId()) == 17) {
                phive("DBUserHandler")->removeBlock($user);
            }
            phive('SQL')->sh($user->getId())
                ->delete('triggers_log', ['trigger_name' => $trigger_name, 'user_id' => $user->getId()]);
            $this->uh->logAction(
                $user,
                "TIS flag {$trigger_name} has been revoked since user has not reached net_loss thold
                for a {$consecutive_weeks} consecutive weeks",
                'tis-flag-revoked'
            );
        }

        return $results;
    }

    /**
     * @param string $date
     * @param int    $consecutive_weeks
     * @param int    $net_loss_thold
     * @param string $net_loss_operator
     *
     * @return array
     * @throws Exception
     */
    protected function intensiveGamblerQueryComponentsSupplier(
        string $date,
        int $consecutive_weeks,
        int $net_loss_thold,
        string $net_loss_operator
    ): array {
        $join_week = function (string $week, string $start_date, string $end_date) {
            return "LEFT JOIN (
                {$this->netLossDBQuery($start_date, $end_date)}
                ) week{$week} ON week{$week}.user_id = u.id\n";
        };
        $join_weeks = "";
        $where_net_loss_per_week_statement = "";
        $select = ["u.id as user_id"];
        for ($n = 1; $n <= $consecutive_weeks; $n++) {
            $start_date = Carbon::parse($date)->subWeeks($n)->startOfWeek(CarbonInterface::MONDAY);
            $end_date = Carbon::parse($start_date)->endOfWeek(CarbonInterface::SUNDAY);
            $join_weeks .= $join_week($n, $start_date->toDateString(), $end_date->toDateString());
            $where_net_loss_per_week_statement .= "AND week{$n}.net_loss $net_loss_operator {$net_loss_thold}\n";
            $select[] = "week{$n}.net_loss as week{$n}_net_loss";
        }
        $select = implode(", ", $select);

        return [
            $select,
            $join_weeks,
            $where_net_loss_per_week_statement,
        ];
    }

    /**
     * Returns DB query to fetch users Net Loss for the requested period
     * net loss = starting balance + deposits - withdrawals - end balance
     *
     * @param string   $start_date Y-m-d
     * @param string   $end_date   Y-m-d
     * @param int|null $user_id
     *
     * @return string
     */
    public function netLossDBQuery(string $start_date, string $end_date, ?int $user_id = null): string
    {
        $where = $user_id ? " WHERE u.id = '{$user_id}'" : "";

        return "SELECT
                    u.id as user_id,
                    (
                        IFNULL(balance_start.balance, 0) +
                        IFNULL(uds.deposits, 0) -
                        IFNULL(uds. withdrawals, 0) -
                        IFNULL(balance_end.balance, 0)
                    ) as net_loss
                FROM users u
                LEFT JOIN (
                    SELECT balance_start.cash_balance as balance, balance_start.user_id as user_id,
                    ROW_NUMBER() OVER(PARTITION BY balance_start.user_id ORDER BY balance_start.balance_date DESC) as row_num
                    FROM external_regulatory_user_balances balance_start
                    WHERE balance_start.balance_date < '{$start_date}') balance_start
                    ON balance_start.user_id = u.id AND balance_start.row_num = 1
                LEFT JOIN (
                    SELECT balance_end.cash_balance as balance, balance_end.user_id as user_id,
                    ROW_NUMBER() OVER(PARTITION BY balance_end.user_id ORDER BY balance_end.balance_date DESC) as row_num
                    FROM external_regulatory_user_balances balance_end
                    WHERE balance_end.balance_date <= '{$end_date}') balance_end
                    ON balance_end.user_id = u.id AND balance_end.row_num = 1
                LEFT JOIN (
                    SELECT SUM(uds.deposits) as deposits, SUM(uds.withdrawals) as withdrawals, uds.user_id as user_id FROM users_daily_stats uds
                    WHERE uds.`date` BETWEEN '{$start_date}' AND '{$end_date}' GROUP BY uds.user_id) uds
                    ON uds.user_id = u.id
                {$where}";
    }

    /**
     * RG66 - Customer has Net Deposit of X within last Y days
     * Net Deposit = Total Deposits - Total Withdrawals
     *
     * @param DBUser $user
     *
     * @return bool
     */
    public function hasXNetDepositInLastYDays(DBUser $user): bool
    {
        $trigger = "RG66";
        $user_id = $user->getId();

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger)) {
            return false;
        }

        $configs = $this->getAndCacheConfig('RG', "{$trigger}-net-deposit", []);
        $days = $this->getAndCacheConfig('RG', "{$trigger}-net-deposit-days", 30);
        $user_currency = $user->getCurrency();

        foreach ($configs as $net_deposit => $countries) {
            $net_deposit = (int) $net_deposit;

            if (!in_array($user->getCountry(), $countries)) {
                continue;
            }

            $deposits = phive('SQL')->sh($user_id)
                ->getValue(
                    "SELECT IFNULL(SUM(amount), 0) as sum
                    FROM deposits
                    WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$days} DAY
                    AND status != 'disapproved'
                    AND user_id = {$user_id}"
                );

            $withdrawals = phive('SQL')->sh($user_id)
                ->getValue(
                    "SELECT IFNULL(SUM(amount), 0) as sum
                    FROM pending_withdrawals
                    WHERE timestamp > CURRENT_TIMESTAMP() - INTERVAL {$days} DAY
                    AND status != 'disapproved'
                    AND user_id = {$user_id}"
                );

            $user_net_deposit = (int) $deposits - (int) $withdrawals;

            $user_net_deposit_converted = mc($user_net_deposit, $user, 'div' );

            if ($user_net_deposit_converted >= $net_deposit) {
                $net_deposit_formatted = nfCents($net_deposit, true);
                $user_net_deposit_formatted = nfCents($user_net_deposit, true);
                $descr = "User has Net Deposit of {$net_deposit_formatted} (original: {$user_net_deposit_formatted} " .
                    "{$user_currency}) within last {$days} days";
                $data = json_encode([
                    'deposit_amount' => $user_net_deposit_formatted,
                    'days' => $days
                ], JSON_THROW_ON_ERROR);
                $this->uh->logTrigger($user, $trigger, $descr, true, false, '', $data);
                return true;
            }
        }

        return false;
    }

    /**
     * Identifies the top losing customers for the last 7 days
     *
     * @return void
     */
    public function customerIsTopLoser()
    {
        $trigger = 'RG67';
        $configs = $this->getAndCacheConfig('RG', "{$trigger}-top-loser", []);

        foreach ($configs as $top_loser_count => $countries) {
            $top_loser_count = (int)$top_loser_count;

            foreach ($countries as $country) {
                $sql = "
                    SELECT uds.user_id, SUM(uds.deposits - uds.withdrawals) AS loss, uds.currency
                    FROM users_daily_stats uds
                    INNER JOIN users u ON uds.user_id = u.id
                    WHERE uds.date >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),INTERVAL 7 DAY)
                    AND uds.date <= DATE_SUB(CURDATE(), INTERVAL (WEEKDAY(CURDATE()) + 1) DAY)
                    AND u.country = '{$country}'
                    GROUP BY uds.user_id
                    HAVING loss > 0
                    ORDER BY loss DESC
                    LIMIT {$top_loser_count};
                ";
                $data = phive('SQL')->loadArray($sql);

                foreach ($data as $loss_data) {
                    $amount = nfCents($loss_data['loss'], true);
                    $description = "Customer is top loser with loss {$amount} {$loss_data['currency']}";
                    $this->uh->logTrigger(cu($loss_data['user_id']), $trigger, $description);
                }
            }
        }
    }

    /**
     * RG68 - N% of Net Deposit Threshold reached
     *
     * @param DBUser $user
     *
     * @return bool
     */
    public function checkNetDepositThreshold(DBUser $user): bool
    {
        $trigger = "RG68";
        $configs = $this->getAndCacheConfig('RG', "{$trigger}-ndl-percentage", []);
        foreach ($configs as $ndl_percentage => $countries) {
            $ndl_percentage = (int) $ndl_percentage;

            if (!in_array($user->getCountry(), $countries)) {
                continue;
            }

            if ($this->uh->hasTriggeredLastPeriod($user->getId(), 'RG68')) {
                continue;
            }

            $net_deposit_limit = rgLimits()->getLimit($user, rgLimits()::TYPE_NET_DEPOSIT, 'month');

            if(empty($net_deposit_limit)){
                continue;
            }

            $current_limit = (int)$net_deposit_limit["cur_lim"];
            $thold = ($current_limit * $ndl_percentage) / 100;

            if ((int)$net_deposit_limit["progress"] >= $thold) {
                $current_limit_to_currency = $current_limit / 100;
                $this->uh->logTrigger($user, $trigger, "User has reached {$ndl_percentage}% of Net Deposit Threshold {$current_limit_to_currency}");
                return true;
            }
        }

        return false;
    }

    /**
     * Identifies the X top losing customers in the Y last Months
     *
     * @return void
     */
    public function topXLosingCustomersInYMonths(): void
    {
        $trigger = 'RG78';
        $top_losers_jur = $this->getAndCacheConfig('RG', "{$trigger}-losing-customers");
        $months_jur = $this->getAndCacheConfig('RG', "{$trigger}-months");

        foreach ($top_losers_jur as $jurisdiction => $top_loser_count) {
            $top_loser_count = (int)$top_loser_count;
            $months = (int)($months_jur[$jurisdiction] ?? 0);

            if($top_loser_count === 0 || $months === 0){
                continue;
            }

            $data = $this->netDepositLossSinceReg($jurisdiction, $top_loser_count, $months);

            if (empty($data)) {
                continue;
            }

            foreach ($data as $loss_data) {
                $user = cu($loss_data['user_id']);
                if ($this->uh->hasTriggeredLastPeriod($loss_data['user_id'], $trigger, $months, 'MONTH')) {
                    continue;
                }

                $description = "Customer is top {$top_loser_count} highest losing customers that have registered in the last {$months} months";
                $data = json_encode([
                    'top_losers_count' => $top_loser_count,
                    'months' => $months
                ], JSON_THROW_ON_ERROR);
                $this->uh->logTrigger($user, $trigger, $description, true, false, '', $data);
            }
        }
    }

    /**
     * Identifies the X top losing young customers in the Y last Months
     *
     * @return void
     */
    public function topXLosingYoungCustomersInYMonths(): void
    {
        $trigger = 'RG80';
        $top_losers_jur = $this->getAndCacheConfig('RG', "{$trigger}-losing-young-customers");
        $months_jur = $this->getAndCacheConfig('RG', "{$trigger}-months");

        foreach ($top_losers_jur as $jurisdiction => $top_young_losers_count) {
            $top_young_losers_count = (int)$top_young_losers_count;
            $months = (int)($months_jur[$jurisdiction] ?? 0);

            if($top_young_losers_count === 0 || $months === 0){
                continue;
            }

            $data = $this->netDepositLossSinceReg($jurisdiction, $top_young_losers_count, $months, true);

            if (empty($data)) {
                continue;
            }

            foreach ($data as $loss_data) {
                if ($this->uh->hasTriggeredLastPeriod($loss_data['user_id'], $trigger, $months, 'MONTH')) {
                    continue;
                }

                $user = cu($loss_data['user_id']);
                $description = "Customer is top {$top_young_losers_count} highest losing young customers that have registered in the last {$months} months";
                $data = json_encode([
                    'top_young_losers_count' => $top_young_losers_count,
                    'months' => $months
                ], JSON_THROW_ON_ERROR);
                $this->uh->logTrigger($user, $trigger, $description, true, false, '', $data);
            }
        }
    }

    /**
     * @param string $jurisdiction
     * @param int $top_loser_count
     * @param int $months_number
     * @param bool $is_young
     * @return array
     */
    private function netDepositLossSinceReg(string $jurisdiction, int $top_loser_count, int $months_number, bool $is_young = false): array
    {
        $where = '';
        if($is_young){
            $user_age = (int)phive('Licensed')->getSetting('jurisdiction_young_age_map')[$jurisdiction];
            $where = "AND CAST(DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), u.dob)), '%Y') AS INT) < {$user_age}";
        }
        $sql = "SELECT uds.user_id, ROUND((SUM(uds.deposits - uds.withdrawals)/c.multiplier),0) AS loss, uds.currency
                FROM users_daily_stats uds
                INNER JOIN users u ON uds.user_id = u.id
                LEFT JOIN currencies c ON c.code = uds.currency
                LEFT JOIN users_settings AS us ON (u.id = us.user_id AND us.setting = 'jurisdiction')
                WHERE u.register_date >= DATE_SUB(CURDATE(), INTERVAL {$months_number} MONTH)
                AND u.register_date <= CURDATE()
                AND us.value = '{$jurisdiction}'
                {$where}
                GROUP BY uds.user_id
                HAVING loss > 0
                ORDER BY loss DESC LIMIT {$top_loser_count}";
        $result = phive('SQL')->shs()->loadArray($sql);
        usort($result, function ($a, $b) {
            return $a['loss'] > $b['loss'] ? -1 : 1;
        });

        return array_slice($result, 0, $top_loser_count) ?? [];
    }

    /**
     * If the RG GRS reaches Medium or High Risk by itself,
     * we keep that risk for a minimum of 7 days.
     * After this we have to remove the blocker 'grs_frozen_till'
     *
     * @return void
     */
    public function unfreezeRgGrsCalculation(): void
    {
        $sql = "SELECT u.id FROM users u
                JOIN users_settings us ON u.id = us.user_id
                AND us.setting = 'rg_grs_frozen_till'
                AND NOW() > us.value;";
        $users = phive('SQL')->shs()->loadArray($sql);
        foreach ($users as $user_data) {
            $user = cu($user_data['id']);
            $user->deleteSetting('rg_grs_frozen_till');
        }
    }

    /**
     * RG73 Flag if customer has played X hours during the last y hours.
     *
     * @param DBUser $user
     *
     * @return void
     * @throws JsonException
     */
    public function hasPlayedXHoursInLastYHours(DBUser $user): void
    {
        $trigger = 'RG73';
        $user_id = $user->getId();

        if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, 7)) {
            return;
        }

        $jurisdiction = $user->getJurisdiction();

        // If the config value is 0 or not exists for the jurisdiction the flag is tuned off.
        $hours_played_threshold_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-hours-played"
        );
        if (empty($hours_played_threshold_config[$jurisdiction])) {
            return;
        }
        $hours_played_threshold = (int)$hours_played_threshold_config[$jurisdiction];

        $duration_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-duration"
        );
        if (empty($duration_config[$jurisdiction])) {
            return;
        }
        $duration = (int)$duration_config[$jurisdiction];

        $hours_played = $this->getHoursPlayedInLastXHours($user, $duration);
        if ($hours_played >= $hours_played_threshold) {
            $trigger_description = "Customer played {$hours_played} hours during last {$duration} hours";
            $data = json_encode([
                'number_of_hours_played' => $hours_played,
                'hours_duration' => $duration
            ], JSON_THROW_ON_ERROR);
            $this->uh->logTrigger($user, $trigger, $trigger_description, true, false, '', $data);
        }
    }

    /**
     * RG77 Top X highest depositing customers that have registered in the last Y months.
     *
     * @return void
     * @throws JsonException
     */
    public function topXDepositorsRegisteredInLastYMonths(): void
    {
        $trigger = 'RG77';

        $top_depositors_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-top-depositors"
        );

        $months_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-months"
        );

        foreach ($top_depositors_config as $jurisdiction => $top_depositors) {
            if ($top_depositors == 0) {
                continue;
            }

            if (empty($months_config[$jurisdiction])) {
                continue;
            }

            $months = $months_config[$jurisdiction];
            $top_depositors_to_trigger = phive('SQL')->shs('merge', 'deposits_eur', 'desc')->loadArray($query = "
                SELECT uds.user_id, ROUND((SUM(uds.deposits) / c.multiplier), 0) AS deposits_eur
                FROM users_daily_stats uds
                         INNER JOIN users u ON uds.user_id = u.id
                         LEFT JOIN currencies c ON c.code = uds.currency
                         LEFT JOIN users_settings AS us ON (u.id = us.user_id AND us.setting = 'jurisdiction')
                WHERE u.register_date >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)
                  AND us.value = '{$jurisdiction}'
                GROUP BY uds.user_id
                ORDER BY deposits_eur DESC
                LIMIT {$top_depositors};
            ");

            $to_trigger = array_slice($top_depositors_to_trigger, 0, $top_depositors);

            foreach ($to_trigger as $details) {
                $trigger_description = "User is in the top {$top_depositors} highest depositing customers " .
                    "that have registered in the last {$months} months";
                $data = json_encode([
                    'months' => $months,
                    'top_depositors' => $top_depositors,
                ], JSON_THROW_ON_ERROR);
                $this->uh->logTrigger(
                    $details['user_id'],
                    $trigger,
                    $trigger_description,
                    false,
                    false,
                    '',
                    $data
                );
            }
        }
    }

    /**
     * Gets the floor of number of hours played by a user during the last x hours.
     *
     * @param DBUser $user
     * @param int $hours
     * @return int
     */
    private function getHoursPlayedInLastXHours(DBUser $user, int $hours): int
    {
        $user_id = $user->getId();

        $query = "
            SELECT FLOOR(SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) / 3600) AS total_hours
            FROM
                users_game_sessions
            WHERE
                user_id = {$user_id}
              AND start_time > CURRENT_TIMESTAMP() - INTERVAL {$hours} HOUR;
        ";

        return (int)phive('SQL')->sh($user_id)->loadAssoc($query)['total_hours'] ?? 0;
    }

    /**
     * RG79 Customer is top X highest winning customers in the last Y months.
     *
     * @return void
     * @throws JsonException
     */
    public function topXWinningCustomersRegisteredInLastYMonths(): void
    {
        $trigger = 'RG79';

        $top_winning_customers_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-winning-customers"
        );

        $months_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-months"
        );

        foreach ($top_winning_customers_config as $jurisdiction => $top_customers_count) {
            if ($top_customers_count === 0) {
                continue;
            }

            if (empty($months_config[$jurisdiction])) {
                continue;
            }

            $months = $months_config[$jurisdiction];
            $top_customers = phive('SQL')->shs('merge', 'winnings', 'desc')->loadArray("
                SELECT
                       uds.user_id, ROUND(SUM(uds.withdrawals - uds.deposits)/c.mod, 0) as winnings
                FROM
                     users_daily_stats uds
                INNER JOIN
                         users u ON uds.user_id = u.id
                INNER JOIN
                         currencies c ON c.code = uds.currency
                LEFT JOIN
                         users_settings AS us ON (
                            u.id = us.user_id AND us.setting = 'jurisdiction'
                        )
                WHERE
                    u.register_date >= CURDATE() - INTERVAL {$months} MONTH
                AND
                      u.register_date < CURDATE()
                AND
                      us.value = '{$jurisdiction}'
                GROUP BY
                         uds.user_id
                HAVING
                       winnings > 0
                ORDER BY
                         winnings
                DESC
                LIMIT {$top_customers_count};
            ");

            $customers_to_be_triggered = array_slice($top_customers, 0, $top_customers_count);

            foreach ($customers_to_be_triggered as $details) {
                if ($this->uh->hasTriggeredLastPeriod($details['user_id'], $trigger, $months, 'MONTH')) {
                    continue;
                }

                $user = cu($details['user_id']);
                $trigger_description = "Customer is top {$top_customers_count} highest winning customers in the last {$months} months";
                $data = json_encode([
                    'months' => $months,
                    'top_winning_customers' => $top_customers_count,
                    'user_winnings' => $details['winnings'],
                ], JSON_THROW_ON_ERROR);
                $this->uh->logTrigger(
                    $user,
                    $trigger,
                    $trigger_description,
                    true,
                    false,
                    '',
                    $data
                );
            }
        }
    }

    /**
     * RG81 Top X unique bets customers in the last Y days.
     *
     * @return void
     * @throws JsonException
     */
    public function topXUniqueBetsCustomersRegisteredInLastYDays(): void
    {
        $trigger = 'RG81';

        $top_unique_bets_customers_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-top-unique-bets-customers"
        );

        $days_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-days"
        );

        foreach ($top_unique_bets_customers_config as $jurisdiction => $top_customers_count) {
            if ($top_customers_count == 0) {
                continue;
            }

            if (empty($days_config[$jurisdiction])) {
                continue;
            }

            $days = $days_config[$jurisdiction];
            $top_customers = $this->replica->shs('merge', 'unique_bets', 'desc')->loadArray("
                SELECT udub.user_id,
                       COUNT(DISTINCT amount) AS total_unique_bets
                FROM users_daily_unique_bets udub
                         INNER JOIN
                     users_settings us ON
                         (udub.user_id = us.user_id AND us.setting = 'jurisdiction' AND us.value = '{$jurisdiction}')
                WHERE udub.date < CURDATE()
                  AND udub.date >= CURDATE() - INTERVAL {$days} DAY
                GROUP BY udub.user_id
                ORDER BY total_unique_bets DESC
                LIMIT {$top_customers_count};
            ");

            $customers_to_be_triggered = array_slice($top_customers, 0, $top_customers_count);

            foreach ($customers_to_be_triggered as $details) {
                $user_id = $details['user_id'];

                if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, $days, 'DAY')) {
                    continue;
                }

                $user = cu($user_id);

                $trigger_description = "Customer is top {$top_customers_count} customers who registered at any time " .
                    "with the highest number of unique bets in the last {$days} days.";
                $data = json_encode([
                    'days' => $days,
                    'top_unique_bets_customers' => $top_customers_count,
                    'user_unique_bet_count' => $details['unique_bets'],
                ], JSON_THROW_ON_ERROR);
                $this->uh->logTrigger(
                    $user,
                    $trigger,
                    $trigger_description,
                    true,
                    false,
                    '',
                    $data
                );
            }
        }
    }

    /**
     * RG82 Customer is top X customers who registered at any time with the highest amount
     * of time spent on site in the last Y days.
     *
     * @return void
     * @throws JsonException
     */
    public function topXUsersTimeSpentOnSite(): void
    {
        $trigger = 'RG82';

        $top_time_spent_customers_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-top-time-spent-customers"
        );

        $days_config = $this->getAndCacheConfig(
            'RG',
            "$trigger-days"
        );

        foreach ($top_time_spent_customers_config as $jurisdiction => $top_time_spent_customers) {
            if ($top_time_spent_customers == 0) {
                continue;
            }

            if (empty($days_config[$jurisdiction])) {
                continue;
            }

            $days = $days_config[$jurisdiction];
            $top_time_spent_customers_to_trigger = phive('SQL')->shs('merge', 'total', 'desc')
                ->loadArray("
                    SELECT us.user_id, SUM(TIMESTAMPDIFF(SECOND, us.created_at, us.ended_at)) AS total
                    FROM users_sessions AS us
                    JOIN users_settings AS jur ON jur.user_id = us.user_id
                      AND setting = 'jurisdiction'
                      AND jur.value = '{$jurisdiction}'
                    WHERE us.created_at >= NOW() - INTERVAL {$days} DAY
                    GROUP BY us.user_id
                    ORDER BY total DESC
                    LIMIT {$top_time_spent_customers};
            ");

            $to_trigger = array_slice($top_time_spent_customers_to_trigger, 0, $top_time_spent_customers);

            $trigger_description = "Customer is top {$top_time_spent_customers} customers who registered " .
                "at any time with the highest amount of time spent on site in the last {$days} days.";
            $data = json_encode([
                'top_time_spent_customers' => $top_time_spent_customers,
                'days' => $days,
            ], JSON_THROW_ON_ERROR);

            foreach ($to_trigger as $details) {
                $user_id = $details['user_id'];

                if ($this->uh->hasTriggeredLastPeriod($user_id, $trigger, $days)) {
                    continue;
                }

                $this->uh->logTrigger(
                    $user_id,
                    $trigger,
                    $trigger_description,
                    true,
                    false,
                    '',
                    $data
                );
            }
        }
    }

    /**
     *
     * RG HELPERS - START >>>
     *
     */

    /**
     * Return the last $num_of_bets bets for a user, it query "bets" table so we try to keep this as small as possible by
     * filtering on the user_id, created_at and limiting the query by $num_of_bets only.
     * Cause this query can be used multiple times we cache the result to be reused.
     *
     * @param $user_id
     * @param $num_of_bets
     * @param null $date
     * @return array
     */
    private function getLastXBetsForUser($user_id, $num_of_bets, $date = null)
    {
        // if it was already called before with more results we can reuse this instead of doing another query.
        if (!empty($this->current_day_last_x_bets) && !empty($this->current_day_last_x_bets[$user_id]) && count($this->current_day_last_x_bets[$user_id]) >= $num_of_bets) {
            return array_slice($this->current_day_last_x_bets[$user_id], 0, $num_of_bets);
        }

        if (empty($date)) {
            $date = phive()->today();
        }

        $query = "
            SELECT
                *
            FROM
                bets
            WHERE
                created_at BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'
                AND user_id = {$user_id}
            ORDER BY
                 id DESC
            LIMIT
                {$num_of_bets}
        ";

        $last_x_bets = $this->replica
            ->sh($user_id)
            ->loadArray($query);

        $this->current_day_last_x_bets[$user_id] = $last_x_bets;

        return $last_x_bets;
    }

    /**
     * Get average lifetime "bet_per_spin" and "daily_wager" for the user, by default get everything starting from yesterday.
     * It caches the result in redis to avoid doing this query more than once a day, being results from yesterday they should never change.
     *
     * We fetch "bets > 0" (even if bet_count is > 0) cause we want to exclude freespins from calculation, or it will decrease the AVG value.
     *
     * IMPORTANT: in case of recalculation we need to clear out all "rg-bets-avg-lifetime-until-******" keys for the $date.
     *
     * @param int $user_id
     * @param string $date
     * @return array
     */
    private function getLifetimeAverage(int $user_id, string $date): array
    {
        $result = json_decode(phMgetShard('rg-bets-avg-lifetime-until-' . $date, $user_id));
        if (!empty($result)) {
            return (array)$result; // json_decode create an stdClass
        }

        $sql = "
            SELECT
                COALESCE(SUM(bets),0) as amount,
                COALESCE(SUM(bets_count),0) AS bet_count,
                COUNT(*) as num_of_days
            FROM (
                SELECT
                    COALESCE(SUM(bets),0) as bets,
                    COALESCE(SUM(bets_count),0) AS bets_count
                FROM
                    users_daily_game_stats
                WHERE
                    user_id = {$user_id}
                    AND date <= '{$date}'
                    AND bets > 0
                GROUP BY
                    date
            ) bet_stats_per_day
        ";

        $average = $this->replica->sh($user_id)->loadAssoc($sql);
        // if any of the fields is empty it means we don't have data so we set everything to 0.
        if (empty($average) || (int)$average['amount'] === 0 || (int)$average['bet_count'] === 0 || (int)$average['num_of_days'] === 0) {
            $result = [
                'bet_per_spin' => 0,
                'daily_wager' => 0
            ];
        } else {
            $result = [
                'bet_per_spin' => $average['amount'] / $average['bet_count'],
                'daily_wager' => $average['amount'] / $average['num_of_days'],
            ];
        }

        phMsetShard('rg-bets-avg-lifetime-until-' . $date, json_encode($result), $user_id, 86400); // 1 day

        return $result;
    }

    /**
     * Returns the daily average "bet_per_spin" and "daily_wager" for each user.
     * We fetch "bets > 0" (even if bet_count is > 0) cause we want to exclude freespins from calculation, or it will decrease the AVG value.
     *
     * @param int $user_id
     * @param string $date
     * @return array
     */
    private function getLastDayAverage(int $user_id, string $date): array
    {
        // if it was already called before for that user we reuse the data.
        if (!empty($this->last_day_average_bets) && !empty($this->last_day_average_bets[$user_id])) {
            return $this->last_day_average_bets[$user_id];
        }

        $sql = "
            SELECT
                COALESCE(SUM(bets),0) as amount,
                COALESCE(SUM(bet_count),0) AS bet_count
            FROM
                users_realtime_stats
            WHERE
                user_id = {$user_id}
                AND date = '{$date}'
                AND bets > 0
        ";

        $average = $this->replica->sh($user_id)->loadAssoc($sql);

        // if any of the fields is empty it means we don't have data so we set everything to 0.
        if (empty($average) || (int)$average['amount'] === 0 || (int)$average['bet_count'] === 0) {
            $result = [
                'bet_per_spin' => 0,
                'daily_wager' => 0
            ];
        } else {
            $result = [
                'bet_per_spin' => $average['amount'] / $average['bet_count'],
                'daily_wager' => $average['amount'],
            ];
        }

        $this->last_day_average_bets[$user_id] = $result;

        return $result;
    }

    /**
     * Check if more than X cancellation of withdrawals were attempted in the last X hours (RG4/7)
     * If amount is exceeded it will fire the respective trigger.
     *
     * @param DBUser $user
     * @param string $trigger
     * @return bool
     */
    private function hasMoreThanXWithdrawalCancellationsInTheLastXHours(DBUser $user, string $trigger): bool
    {
        switch ($trigger) {
            case 'RG4':
                $default_hours = 24;
                $default_withdrawals = 3;break;
            case 'RG7':
                $default_hours = 168; // 1 week
                $default_withdrawals = 9;
                break;
            default:
                return false;
        }
        $last_x_hours = $this->getAndCacheConfig('RG', "$trigger-last-x-hours", $default_hours);
        $canceled_withdrawals_threshold = $this->getAndCacheConfig('RG', "$trigger-canceled-withdrawal-threshold", $default_withdrawals);

        $number_of_canceled_withdrawals = $this->getCancelledWithdrawals($user, "approved_at BETWEEN NOW() - INTERVAL $last_x_hours HOUR AND NOW()");

        if ($number_of_canceled_withdrawals > $canceled_withdrawals_threshold) {
            $this->uh->logTrigger($user, $trigger, "User canceled $number_of_canceled_withdrawals withdrawals in the last $last_x_hours hours");
            return true;
        }

        return false;
    }


    /**
     * Common function used by RG4/7/26 to get number of cancelled withdrawals.
     * TODO see if we can somehow switch this from hours (now - interval) to days (on date), like this we should be able to
     *  do 1 single query with the largest amount of days grouped by date, and handle all calculations in php. /Paolo
     *
     * @param DBUser|int $user
     * @param string $where
     * @return int|mixed
     */
    private function getCancelledWithdrawals($user, string $where)
    {
        $user_id = $user->getId();
        $res = $this->replica
            ->sh($user_id)
            ->loadArray("
                SELECT count(amount) AS number_of_canceled_withdrawals FROM pending_withdrawals
                WHERE user_id = approved_by
                AND user_id = {$user_id}
                AND status = 'disapproved'
                AND {$where}
                GROUP BY user_id
            ");

        if (empty($res) || empty($res[0]['number_of_canceled_withdrawals'])) {
            return 0;
        }

        return $res[0]['number_of_canceled_withdrawals'];
    }

    /**
     *
     * RG HELPERS - END <<<
     *
     */
}
