<?php

use DBUserHandler\DBUserRestriction;

require_once('Arf.php');
require_once ('PSPType.php');

class Aml extends Arf
{
    function __construct(){
        parent::__construct();

        $this->c = phive('Cashier');
    }

    // Commented out for now to avoid calling this when no action is done inside this method
//    function onSessionEnd($uid) {
//        $u = cu($uid);
//    }

    function onGameSessionEnd($uid, $game_session_id) {
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
            $lock = phMsetNx('AML_UGS_' . $uid, $game_session_id, 30);
        }

        $u = cu($uid);
        $this->customerIntendedExtentOfGambling($u);

        phMdel('AML_UGS_' . $uid);
    }

    /**
     * AML14 - 3rd Party Funding
     * Source of funds deriving from 3rd party
     *
     * @param $uid
     * @param $mts_result
     * @param $source
     * @param $neteller_account
     */
    function accountCheck($uid, $mts_result, $source, $neteller_account)
    {
        $u = cu($uid);
        $uid = $u->getId();
        if (@$mts_result['result']['account_info']['owns_account'] === false &&
            $mts_result['result']['account_info']['match_info']['lastName'] == "NO_MATCH") {
            $already_trigger = phive('SQL')->sh($uid)->getValue("SELECT id FROM triggers_log WHERE user_id = $uid AND trigger_name ='AML14' AND txt = '$neteller_account'");
            $extra = json_encode($mts_result['result']['account_info']);
            if (empty($already_trigger))
                $this->uh->logTrigger($u, 'AML14', $source, true, true, '', $extra, $neteller_account);
        }
    }

    public function onRegistration($uid) {

    }

    /**
     * AML2 - PEP & SL Failure
     * PEP & SL Failure
     *
     * OR
     *
     * AML10 - Basic Due Diligence
     * Failed background Electronic checks on name, age and address in UK
     * +
     * RG1 - Age verification
     * Age verification fail upon SDD
     *
     * @param $uid
     * @param $type
     * @param $method
     * @param $result
     */
    function onKycCheck($uid, $type, $method, $result)
    {
        $user = cu($uid);

        if ($type == 'pep') {
            $this->uh->logTrigger($user, 'AML2', "PEP and sanction list check on {$method} failed. Result: {$result}");
        } elseif ($type == 'age') {
            $this->uh->logTrigger($user, 'AML10', "{$method} check failed with result: {$result}");
            $this->uh->logTrigger($user, 'RG1', "{$method} check failed with result: {$result}");
        }
    }

    // Run just after the current deposit has been saved, in a forked process.
    function onDeposit($uid, $did = '', $timestamp = ''){
        $cf = phive('Config')->getByTagValues('AML');
        $u = cu($uid);

        // Check if we need to restrict the player
        if(empty($u->isRestricted())) {
            $restriction_reason = phive('DBUserHandler')->doCheckRestrict($u);

            if ($restriction_reason) {
                $u->restrict($restriction_reason);
            }
        }

        $this->depositRiskOfTen($u);

        //Risk score based triggers
        $this->riskScoreChecks($u);

        /**
         * AML1 - AML4 Threshold
         * singular or accumulated 'transaction' of = or <€2,000 from registration
         */
        $thold = $cf['AML1'];
        // We don't trigger again if there already is one.
        $this->depositCheck($u, $thold, 'AML1', false, true, false, null, $timestamp);


        /**
         * AML12 - High Deposit Threshold
         * Deposit is > or = to €10,000 in singular transaction or accumulated
         *
         * AML16 - 50k Deposit
         * Deposit is > or = to €50,000 in singular transaction or accumulated
         *
         * hook for licence AML checks
         */
        $this->depositCheck($u, $cf['AML12'], 'AML12', false, false, true, null, $timestamp);
        $this->depositCheck($u, $cf['AML16'], 'AML16', true, false, true, null, $timestamp);
        lic('onDepositAMLCheck', [$u, $timestamp], $u);

        $time = empty($timestamp) ? time() : $timestamp;
        $hours_since_reg = phive()->subtractTimes($time, strtotime($u->data['register_date']), 'h');

        $extra_where = empty($timestamp) ? $timestamp : " AND timestamp < '{$timestamp}'";

        /**
         * AML7 - Consecitve Deposits
         * singular or accumulated deposit of = or <€5,000 from sign up or within 24 - 48 hour time period
         */
        if (!$this->uh->hasTriggeredLastPeriod($uid, 'AML7', 1, 'DAY')) {
            $this->depositCheck($u, $cf['AML7'], 'AML7', true, false, false, 48, $timestamp);
        }

        if($hours_since_reg <= 48){
            /**
             * AML8 - Multiple Source of Funds
             * > or =  3 payment methods registered within 24 - 48 hour of registration
             */
            $type_cnt = count(phive('SQL')->sh($uid, '', 'deposits')->loadArray("SELECT id FROM deposits WHERE user_id = $uid {$extra_where} GROUP BY dep_type"));
            if($type_cnt >= 3)
                $this->uh->logTrigger($u, 'AML8', "$type_cnt deposits since reg");
        }

        /**
         * AML57 - 1k Single/Accumulated transaction
         * Singular or accumulated deposits of €1,000 or more within 30 days using Paysafecard, Flexipin, Neosurf or CashToCode
         */
        if (!$this->uh->hasTriggeredLastPeriod($uid, 'AML57', $cf['AML57-duration-days'])) {
            $hours = ($cf['AML57-duration-days'] ?? 1) * 24;
            $psp = explode(',', $cf['AML57-psp']);
            $this->depositCheck($u, $cf['AML57-deposit-thold'], 'AML57', true, false, false, $hours, $timestamp, $psp);
        }

        /**
         * AML4 - Deposits from  Near Expired CC's
         * New card deposit with exp date < 1 month exceeding <€500 accumulated
         */
        $thold                  = chgCents($this->def_cur, $u, $cf['AML4-money'], 1);
        $months_to_expiry_thold = $cf['AML4-months'];
        $deposit                = phive('Cashier')->getDeposit($did, $uid);
        $res                    = phive('Cashier/Mts')->arf('cCnearExpireGet', [$uid, $months_to_expiry_thold, $thold, $deposit['mts_id'], $timestamp]);
        if(!empty($res))
            $this->uh->logTrigger($u, 'AML4', "Amount of near expiry deposits: {$res['dep_cnt']}, accumulated: {$res['amount_sum']} cents");


        $this->checkPrepaidDeposits($u, $cf);

        $this->performThresholdChecks($u);
    }

    /**
     * Perform threshold check and update CDD status for a user.
     *
     * This function checks if a user meets the threshold conditions for CDD and updates their status accordingly.
     * If the user meets the threshold or has approved documents or verified, no action is taken.
     *
     * @param DBUser $user
     */
    public function performThresholdChecks(DBUser $user): void
    {
        if (!$this->canProceedWithCDDCheckCrossBrand($user)) {
            return;
        }

        $is_single_brand = $user->isSingleBrandUser();

        // Perform the single or cross-brand CDD check based on user type
        $this->checkSingleDepositThreshold($user, $is_single_brand);

        if ($user->isCDDChecked()) {
            return;
        }

        $this->checkTotalTransactionThresholdCrossBrand($user);
    }

    /**
     * Check if we can skip CDD checks for a user based on their verification status or document approval.
     * @param DBUser $user
     * @return bool
     */
    public function canSkipCDDCheck(DBUser $user): bool
    {
        $required_documents_types = lic('getRequiredDocumentsTypes', [], $user)
            ?? lic('getLicSetting', ['required_documents_types_cdd'], $user);

        // Skip if user is verified, CDD-checked, or all required documents are approved
        return $user->isVerified() || $user->isCDDChecked()
            || phive('Dmapi')->areAllUserRequiredDocumentsApproved($user, $required_documents_types);
    }


    /**
     * Common initial checks to determine if CDD checks can proceed.
     *
     * @param DBUser $user
     * @return bool
     */
    public function canProceedWithCDDCheckCrossBrand(DBUser $user): bool
    {
        return $user->shouldUpdateCDDStatus() && !$this->canSkipCDDCheck($user);
    }

    /**
     * Check if the user's total transactions exceed the configured threshold and trigger CDD if necessary.
     *
     * @param DBUser $user
     */
    public function checkTotalTransactionThresholdCrossBrand(DBUser $user): void
    {
        $threshold = $this->getCrossBrandConfig($user, 'threshold');
        if (empty($threshold)) {
            return;
        }

        $remote = getRemote();
        $remote_first_deposit = toRemote($remote, 'getFirstDeposit', [$user->getRemoteId()]);
        $local_first_deposit = $user->hasDeposited();

        if (is_null($remote_first_deposit['result']) || empty($local_first_deposit)) {
            return;
        }

        $total_transactions = $this->calculateTotalTransactionsLocalRemoteFromRegistrationTillNow($user);
        $converted_total = chg($user->getCurrency(), phive('Currencer')->baseCur(), $total_transactions);

        if ($converted_total >= $threshold) {
            $user->triggerCDD("Reached {$threshold} total transaction threshold");
        }
    }

    /**
     * Check if the user's latest deposit exceeds the single deposit threshold
     * and trigger CDD if necessary, handling both single-brand and cross-brand users.
     *
     * @param DBUser $user
     * @param bool $is_single_brand Indicates whether the user is single-brand or cross-brand.
     */
    public function checkSingleDepositThreshold(DBUser $user, bool $is_single_brand): void
    {
        if($this->canSkipCDDCheck($user)){
            return;
        }

        $single_deposit_threshold = $is_single_brand
            ? lic('getLicSetting', ['cdd_single_deposit_threshold'], $user)  // For single-brand
            : $this->getCrossBrandConfig($user, 'cdd_single_deposit_threshold');  // For cross-brand

        if (empty($single_deposit_threshold)) {
            return;
        }

        $last_deposit = phive('CasinoCashier')->getLatestDeposit($user);
        $last_deposit_converted = mc($last_deposit['amount'], $user, 'div');

        $reached_threshold = $last_deposit_converted >= $single_deposit_threshold;

        if ($reached_threshold) {
             if ($is_single_brand) {
                 $user->restrict(DBUserRestriction::CDD_CHECK);
             } else {
                 $user->triggerCDD("Reached single deposit threshold of {$single_deposit_threshold}");
                 $user->updateCDDFlagOnDocumentStatusChange(false);
             }
        }
    }

    /**
     * Fetch the threshold setting for a user.
     *
     * @param DBUser $user
     * @param string $key
     * @return mixed
     */
    public function getCrossBrandConfig(DBUser $user, string $key)
    {
        return lic('getLicSetting', ['cross_brand'], $user)[$key];
    }

    /**
     * @param $user
     * @return int
     */
    public function calculateTotalTransactionsLocalRemoteFromRegistrationTillNow($user): int
    {
        $user_id = $user->getId();
        $register_date = strtotime($user->data['register_date']);
        $current_timestamp = phive()->hisNow();

        $transactions = phive('Cashier')->getTransactionSumsByUserIdProvider(
            $user_id,
            $register_date,
            $current_timestamp
        );

        $sum = $transactions['sum_deposits'] + $transactions['sum_withdrawals'];

        $remote_id = $user->getRemoteId();
        $remote_sum = 0;

        if ($remote_id) {
            $remote = toRemote(getRemote(), 'getRemoteUserWithdrawDepositTotal', [$remote_id, $user->getCurrency()]);
            $remote_sum = $remote['success'] ? (int)$remote['result'] : 0;
        }

        return $sum + $remote_sum;
    }

    /**
     * AML33 Multiple Bank account Deposits: > or = 2 or more Bank Transfers not matching name on account
     *
     * Note: if setting not_matching_receiver_name_cnt is ever to be removed from the code, we need to remove too from the database
     *
     * @param int|DBUser $uid
     * @param $receiver
     * @param $provider
     */
    public function bankReceiverCheck($uid, $receiver, $provider)
    {
        /** @var DBUser $user */
        $user = cu($uid);
        $received = removeSpecialCharacter($receiver);
        $lastname = removeSpecialCharacter($user->data['lastname']);

        // details match - don't trigger
        if (strpos($received, $lastname) !== false) {
            return false;
        }

        $tracking_setting = intval($user->getSetting('not_matching_receiver_name_cnt'));
        if ($tracking_setting < 1) {
            $user->setSetting('not_matching_receiver_name_cnt', ($tracking_setting + 1));
            return;
        } else {
            $this->uh->logTrigger($user, 'AML33', "Back deposit using {$provider} with receiver name: {$receiver} and name on account {$user->getFullName()}.");
            $user->deleteSetting('not_matching_receiver_name_cnt');
        }

    }

    public function onWithdrawal($uid, $wid = null) {

        $user          = cu($uid);
        $uid           = $user->getId();

        // Check if we need to restrict the player
        if(empty($user->isRestricted())) {
            $restriction_reason = phive('DBUserHandler')->doCheckRestrict($user);

            if ($restriction_reason) {
                $user->restrict($restriction_reason);
            }
        }

        /**
         * AML1 - AML4 Threshold
         * singular or accumulated 'transaction' of = or <€2,000 from registration
         */
        if (empty(phive('UserHandler')->getNewestTrigger($user, 'AML1'))) {
            $aml1_thold = $this->getAndCacheConfig('AML','AML1', 200000);
            $sum   = $this->c->getWithdrawalsInPeriod($uid, phive()->getZeroDate(), "IN('approved', 'pending')", '', 'timestamp', "SUM(amount) AS amount_sum")[0]['amount_sum'];
            $sum   = chgToDefault($user, $sum, 1);
            if ($sum > $aml1_thold){
                $this->uh->logTrigger($user, 'AML1', "Amount $sum over the threshold: $aml1_thold", false, true, floor($sum / $aml1_thold));
            }
        }

        /**
         * AML6 - No Turn Over
         * deposits = or > €3,000 where wager is 30% of deposit followed by Withdrawal
         */
        $thold         = $this->getAndCacheConfig('AML', 'AML6-thold', 300000);
        $thold_percent = $this->getAndCacheConfig('AML', 'AML6-percent', 200) / 100;
        $where         = empty($wid) ? "user_id = {$uid}" : "w_id2 = {$wid}";
        $ml            = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM money_laundry WHERE {$where} ORDER BY id DESC LIMIT 1");
        $dep_thold     = chg($this->def_cur, $user, $thold, 1);
        $wager_thold   = $ml['dep_sum'] * $thold_percent;

        if ($ml['dep_sum'] >= $dep_thold && $ml['wager_sum'] < $wager_thold) {
            $this->uh->logTrigger($user, 'AML6', "Dep sum: {$ml['dep_sum']}, wager sum: {$ml['wager_sum']}");
        }

        $this->performThresholdChecks($user);
        $this->highRiskBehaviourWithPrepaidCards($user);
    }

    /**
     * @param DBUser $u
     * @param int $thold In cents
     * @param string $tag Trigger name
     * @param bool $retrigger
     * @param bool $date Do we only want to re-trigger once per day?
     * @param bool $counter If true then modify the $thold = (how many times flag was triggered + 1) * $thold
     * @param int $timeframe In hours. Includes singular transaction or accumulated transactions within N hours
     * @param $timestamp End date of time range
     * @param array $psp List of Payment service providers. E.g. ['worldpay', 'trustly', 'skrill']
     *
     * @return bool
     */
    function depositCheck(
        $u,
        $thold,
        $tag,
        $retrigger = true,
        $date = true,
        $counter = false,
        $timeframe = 0,
        $timestamp = '',
        $psp = []
    ){

        $max_dep = 0;
        $dep_sum = 0;
        $where = $andWhere = "";
        if(! empty($psp)) {
            $psp_list = "'" . implode("', '", $psp). "'";
            $where = "dep_type IN ({$psp_list})";
            $andWhere = "AND {$where}";
        }

        // if timespan is 0 then check maximum deposit and deposited sum, irrelevant of time
        if ($timeframe == 0) {

            $max_dep = chg(
                $u,
                $this->def_cur,
                phive('Cashier')->getUserDeposits($u->getId(), " ORDER BY amount DESC ", $where)[0]['amount'] ?? 0,
                1
            );
            $dep_sum = chg(
                $u,
                $this->def_cur,
                phive('Cashier')->getUserDepositSum($u->getId(), "'approved'", $andWhere),
                1
            );
        } else {
            // check the deposits made within a timeframe
            if (empty($timestamp)) {
                $edate = phive()->hisNow();
                $s_date = "-" . $timeframe . " hour";
                $sdate = phive()->hisMod($s_date);
            } else {
                $edate = $timestamp;
                $s_date = "-" . $timeframe . " hour";
                $sdate = phive()->hisMod($s_date, $timestamp);
            }

            $dep_sum = phive('Cashier')->sumDepositsByTypeDate('', $sdate, $edate, $u->getId(), $andWhere, $this->def_cur);
        }

        $count = $this->uh->getTriggerCounter($u->getId(), $tag);

        //taking the maximum from deposited amount or accumulated amount
        if ($dep_sum >= $max_dep) {
            $sum = $dep_sum;
        } else {
            $sum = $max_dep;
        }

        $cnt = floor($sum / $thold);

        if($counter){
           $thold = ($count + 1) * $thold;
        }

        if($sum >= $thold){
            if ($timeframe == 0){
                $this->uh->logTrigger($u, $tag, "Amount $sum over the threshold: $thold", $retrigger, $date, $cnt);
            } else {
                $this->uh->logTrigger($u, $tag, "$sum in deposits in the last $timeframe hours", $retrigger, $date, $cnt);
            }

            // No need to check time period if we have a single deposit that is larger than the threshold
            return true;
        }

        return false;
    }

    function onLogin($uid)
    {
        $u = cu($uid);

        // If the user is empty we can't do anything
        if (empty($u)) {
            return;
        }

        // We get the current IP as a fresh attribute to be 100% we aren't looking at the prior IP used to login.
        // The new IP is saved to users.cur_ip before this code is invoked so should be good.
        $cur_ip = $u->getAttr('cur_ip', true);

        /**
         * AML11 - SAR Activity
         * Activity on Account after SAR has been raised and listed under customer Monitoring flag
         *
         * AML13 - Monitored Accounts
         * Account with status set to Monitoring due to AML suspicions trigger
         */
        foreach (['AML11' => 'sar-flag', 'AML13' => 'amlmonitor-flag'] as $flag => $user_setting) {
            if ($u->hasSetting($user_setting))
                $this->uh->logTrigger($u, $flag, "Has the $user_setting setting", false);
        }

        /**
         * AML15 - Country Mismatch
         * IP deriving from VPN, TOR, Open Proxy to detect high risk Geo Loc
         */
        try {
            $udger_data = phive('IpBlock')->udgerInfo($cur_ip);
            $code = $udger_data['ip_address']['ip_classification_code'];
        } catch (Exception $e) {
            phive('Logger')->error('Aml-onLogin-error', ['error' => json_encode($e->getMessage()), 'stack_trace' => json_encode($e->getTrace())]);
        }
        // We have something non-standard
        if (!empty($code) && $code != 'unrecognized') {
            $u->updateSession(['ip_classification_code' => $code]);
            $this->uh->logTrigger($u, 'AML15', "IP classification: {$udger_data['ip_address']['ip_classification']}, IP: $cur_ip, IP Country: {$udger_data['ip_address']['ip_country']}, User country: {$u->getAttr('country')}");

        }

        $this->checkCookieIP($u, $cur_ip);
        $this->recurrentPersonalRegisterCheck($u);
    }

    /**
     * Recurrent personal register check done every 6 months to log when there are no changes
     * OR to update and log the changes on the user information
     *
     * @param DBUser $user
     */
    public function recurrentPersonalRegisterCheck($user)
    {
        if (empty($user->getNid())) {
            return;
        }

        // register_date is less then 6 months ago
        if ($user->getData('register_date') > phive()->hisMod("- 6 month")) {
            return;
        }

        if (!$user->hasSettingExpired('last_recurrent_personal_check', 6, 'month', 'value')) {
            return;
        }

        $lookup_data = lic('lookupNid', [$user], $user);
        if (empty($lookup_data)) {
            return;
        }

        $user_data = $user->getData();
        $description = "Customer identity and address data for residents existing in the national population register matches";
        $lookup_data = lic('getPersonLookupHandler', [], $user)->mapLookupData($lookup_data->getResponseData());
        $updated_fields = [];

        foreach ($lookup_data as $key => $value) {
            if (array_key_exists($key, $user_data) && !empty($value) && ($value != $user_data[$key])) {
                $updated_fields[$key] = $value;
            }
        }

        if (!empty($updated_fields)) {
            $user->updateData($updated_fields);
            $description = "Customer identity details automatically updated from National Population Register: " . implode(',', array_keys($updated_fields));
        }

        $this->uh->logAction($user, $description, 'recurrent-personal-register-check');
        $user->refreshSetting('last_recurrent_personal_check', phive()->hisNow());
    }

    /**
     * AML9: Cookie and IP history to link customer data. Should only trigger if both cookie and ip history link to customer data.
     *       Triggers only once.
     *
     * @param DBUser $user
     * @param mixed $cur_ip
     */
    public function checkCookieIP($user, $cur_ip = null)
    {
        $user = cu($user);

        if (!empty($this->uh->getNewestTrigger($user, 'AML9'))) { // We only flag once and we avoid the rest of the logic on loign
            return;
        }

        if (empty($cur_ip)) {
            $cur_ip = $user->getAttr('cur_ip', true);
        }

        $fingerprint = $user->getCurrentSession()['fingerprint'];
        $same_ip_sessions = phive('SQL')->shs('merge', '', null, 'users_sessions')->loadArray("SELECT  distinct(user_id), ip  FROM users_sessions WHERE user_id != {$user->getId()} AND ip = '{$cur_ip}' AND ip != '' AND fingerprint = '{$fingerprint}' AND fingerprint !='' ");
        if (!empty($same_ip_sessions)) {
            $ids = [];
            foreach ($same_ip_sessions as $same_ip_session) {
                $same_ip_user = cu($same_ip_session['user_id']);
                if (empty($same_ip_user)) {
                    continue;
                }
                $ids[] = "u_" . $same_ip_user->getUsername() . "_u";
            }
            $uids = implode(" ", $ids);
            $this->uh->logTrigger($user, 'AML9', "Same IP ({$cur_ip}) and cookie {$fingerprint} as: $uids", false);
        }
    }

    // Run in fork at the beginning, otherwise the timespans will be off with up to an hour or two.
    function everydayCron($day = ''){
        error_log("AML1". date('Y-m-d H:i:s'));
        $this->checkHeadsUpChipDumping($day);
        error_log("AML2". date('Y-m-d H:i:s'));

        error_log("AML52-start". date('Y-m-d H:i:s'));
        $this->selfExcluderRefund();
        error_log("AML52-end". date('Y-m-d H:i:s'));
    }


    /**
     * AML5 deposits = or > 10 pre paid cards or Paysafe vouchers
     *
     * @param DBUser $user
     */
    public function checkPrepaidDeposits($user, $cf)
    {
        $trigger_name = 'AML5';
        if ($this->uh->hasTriggeredLastPeriod($user, $trigger_name, 7)) {
            return;
        }

        $thold       = $cf['AML5-count-thold'];
        $money_thold = chg(phive('Currencer')->baseCur(), $user, $cf['AML5-money-thold']);
        $time_span   = $cf['AML5-time-span'];
        $res         = phive('Cashier/Mts')->arf('getPrepaidDepositCount', [$user->getId(), $time_span]);

        $total_cnt    = array_sum(array_column($res, 'cnt'));
        $total_amount = array_sum(array_column($res, 'amount_sum'));

        if($total_cnt >= $thold && $total_amount >= $money_thold) {
            $msg = '';

            foreach ($res as $dep) {
                $msg .= " {$dep['cnt']} {$dep['supplier']} ";
            }

            // We have more deposits than the threshold so an insert or update is in order
            if($total_cnt >= $thold){
                $trigger     = $this->uh->getNewestTrigger($user, $trigger_name);
                $trigger_cnt = $trigger['cnt'] + 1;
                if(empty($trigger) || $trigger_cnt > $thold)
                    $this->uh->logTrigger($user, $trigger_name, $msg, true, false, 0);
                else{
                    $trigger['cnt']++;
                    phive('SQL')->sh($user, 'id')->save('triggers_log', $trigger);
                }
            }
        }
    }

    /**
     * @param DBUser $user
     */
    public function riskScoreChecks($user)
    {

        // reason for specifically getting all this config values in an explicit way is to be able to set default values
        $triggers = [
            /**
             * AML17 - High Risk - Risk Profile Rating with Global score range 80 - 89
             */
            'AML17' => [
                'score' => phive('Config')->valAsArray('AML', 'AML17-score', ' ', ':', 'min:80 max:89'),
                'time' => $this->getAndCacheConfig('AML', 'AML17-time', 6),
                'frequency' => $this->getAndCacheConfig('AML', 'AML17-frequency', 'MONTH'),
            ],
            /**
             * AML19 - High Risk - AML Risk Profile Rating with Global score range 90 - 99
             */
            'AML19' => [
                'score' => phive('Config')->valAsArray('AML', 'AML19-score', ' ', ':', 'min:90 max:99'),
                'time' => $this->getAndCacheConfig('AML', 'AML19-time', 4),
                'frequency' => $this->getAndCacheConfig('AML', 'AML19-frequency', 'MONTH'),
            ],
            /**
             * AML23 - High Risk - AML Risk Profile Rating with Global score eq 100
             */
            'AML23' => [
                'score' => phive('Config')->valAsArray('AML', 'AML23-score', ' ', ':', 'min:100 max:100'),
                'time' => $this->getAndCacheConfig('AML', 'AML23-time', 1),
                'frequency' => $this->getAndCacheConfig('AML', 'AML23-frequency', 'MONTH'),
            ],
            /**
             * AML28 - High Risk - AML Risk Profile Rating of people with a NGR last 12 months score eq 100
             */
            'AML28' => [
                'score' => phive('Config')->valAsArray('AML', 'AML28-score', ' ', ':', 'min:100 max:100'),
                'time' => $this->getAndCacheConfig('AML', 'AML28-time', 1),
                'frequency' => $this->getAndCacheConfig('AML', 'AML28-frequency', 'MONTH'),
                'target' => $this->getAndCacheConfig('AML', 'AML28-target', 'ngr_last_12_months'),
            ]
        ];

        $global_aml_score = $this->getLatestRatingScore($user->getId(), 'AML');

        foreach ($triggers as $trigger_name => $config) {
            if ($this->uh->hasTriggeredLastPeriod($user, $trigger_name, $config['time'], $config['frequency'])) {
                continue;
            }

            $score = $global_aml_score;
            $score_type = 'GRS';
            if(isset($config['target'])) {
                $score = $this->getLatestRatingScore($user->getId(), 'AML', $config['target']);
                $score_type = $config['target'];
            }

            if ($score >= $config['score']['min'] && $score <= $config['score']['max']) {
                $this->uh->logTrigger($user, $trigger_name, "{$trigger_name} was triggered, user AML {$score_type} score = {$score}");
            }
        }
    }

    /**
     * NEW AML43
     *      AML Risk Profile Rating of people with a Deposited amount last 12 months score of 100
     *
     * OLD  AML43
     *      Checks if the email address contains the username of the user
     *
     * @param DBUser $user
     * @return boolean True if the flag has been set
     */
    public function depositRiskOfTen($user)
    {
        $trigger = 'AML43';
        $time = $this->getAndCacheConfig('AML', "$trigger-time", 1);
        $thold = phive('Config')->valAsArray('AML', "$trigger-score", ' ', ':', 'min:100 max:100');
        $target = $this->getAndCacheConfig('AML', "$trigger-target", 'deposited_last_12_months');
        $frequency = $this->getAndCacheConfig('AML', "$trigger-frequency", 'MONTH');

        if ($this->uh->hasTriggeredLastPeriod($user, $trigger, $time, $frequency)) {
            return false;
        }

        $aml_dep_last_12_month_score = $this->getLatestRatingScore($user->getId(), 'AML', $target);

        if ($aml_dep_last_12_month_score >= $thold['min'] && $aml_dep_last_12_month_score <= $thold['max']) {
            $this->uh->logTrigger($user, $trigger, "$trigger was triggered");
            return true;
        }

        return false;
    }

    /**
     * AML51 - Incomplete Source of Wealth
     * User has visited withdrawal page more than X days ago without completing SOWD, player is now restricted and enforced to fill in SOWD.
     */
    public function incompleteSourceOfWealthForXDays() {
        $trigger = 'AML51';
        $time = $this->getAndCacheConfig('documents', "sourceoffunds_expiry", '30');
        $frequency = $this->getAndCacheConfig('AML', "$trigger-frequency", 'DAY');

        $users_with_incomplete_sowd = phive('SQL')->shs()->loadArray("
            SELECT user_id FROM users_settings
            WHERE setting = 'source_of_funds_waiting_since'
            AND user_id NOT IN( SELECT user_id FROM users_settings WHERE setting = 'sowd-enforce-verification' AND value = 1)
            AND value <= DATE_SUB(NOW(), INTERVAL $time $frequency)
        ");

        foreach ($users_with_incomplete_sowd as $setting) {
            $user_id = $setting['user_id'];
            $this->uh->logTrigger($user_id, $trigger, "$trigger was triggered - more than $time $frequency without completing SOWD", false);
            // Cause the check to set "sowd-enforce-verification" is done on login only we enforce the verification via CRON, for users who logged in right before the 30day limit.
            cu($user_id)->setSetting('sowd-enforce-verification', 1);
        }
    }

    /**
     * AML52 - Self-Excluder refund
     * Manual refund where balance is >£1.50 Indicator
     */
    public function selfExcluderRefund() {
        $trigger = 'AML52';
        $config = phive('Config')->valAsArray('AML', 'AML52');

        /** @var UserHandler $userHandler */
        $userHandler = phive('UserHandler');

        foreach ($config as $value) {
            list($country, $balance_min) = explode(':', $value);
            $result = phive('SQL')->shs()->loadArray("
                SELECT u.id, u.currency, u.cash_balance, us.setting AS us_setting, us.value AS us_value FROM users AS u
                LEFT JOIN triggers_log AS tl ON tl.user_id = u.id AND tl.trigger_name = 'AML52' AND tl.created_at > (NOW() - INTERVAL 30 DAY)
                LEFT JOIN users_settings AS us ON us.user_id = u.id AND us.setting IN ('excluded-date', 'unexclude-date', 'external-excluded', 'indefinitely-self-excluded')
                WHERE u.country = '$country' AND (u.cash_balance / 100) > $balance_min AND tl.id IS NULL AND us.id IS NOT NULL;
            ");

            $excluded_users = [];

            foreach ($result as $el) {
                if (empty($excluded_users[$el['id']])) {
                    $excluded_users[$el['id']] = [
                        'balance' => $el['cash_balance'],
                        'currency' => $el['currency'],
                    ];
                }
                $excluded_users[$el['id']][$el['us_setting']] = $el['us_value'];
            }


            foreach ($excluded_users as $user_id => $data) {
                $is_self_excluded = (!empty($data['excluded-date']) && !empty($data['unexclude-date'])) || !empty($data['indefinitely-self-excluded']);
                $is_external_self_excluded = !empty($data['external-excluded']);

                if (!$is_self_excluded && !$is_external_self_excluded) {
                    continue;
                }

                $user_balance = nfCents($data['balance'], true);
                $this->uh->logTrigger($user_id, $trigger, "Self excluded $country user balance of $user_balance {$data['currency']} exceeds $balance_min {$data['currency']}.");
                phive('DBUserHandler/Booster')->releaseBoosterVault($user_id);

                $filterSuppliers = $userHandler->suppliersAvailableForAutoPayout();
                $userHandler->aml52Payout((int)$user_id, $filterSuppliers);
            }
        }
    }

    /**
     * AML3 - Chip Dumping BOS
     * heads up session >2 with same player exceeds gain/loss of = or > €100
     * @param $day
     */
    function checkHeadsUpChipDumping($day)
    {
        $thold = $this->getAndCacheConfig('AML', 'AML3-money', 10000);
        $entries_thold = $this->getAndCacheConfig('AML', 'AML3-tournaments', 2);
        // We need to latch on to the fact that in order to chip dump reliably there needs to be spins left.
        $sdate = empty($day) ? phive()->hisMod('-24 hour') : $day . ' 00:00:00';
        $edate = empty($day) ? phive()->hisNow() : $day . ' 23:59:59';
        $tes = phive('SQL')->shs()->loadArray("
          SELECT te.*, t.start_format, t.max_players, t.category, t.cost as dumped
          FROM tournaments t
              INNER JOIN tournament_entries te ON t.id = te.t_id
          WHERE updated_at BETWEEN '$sdate' AND '$edate'
              AND t.category != 'freeroll'
              AND t.max_players = 2
              AND t.start_format = 'sng'
              AND te.spins_left > 0
              AND te.won_amount = 0
              ");
        $grouped = phive()->group2d($tes, 'user_id', false);
        foreach ($grouped as $uid => $group) {
            $total_dumped = phive()->sum2d($group, 'dumped');
            // Has he dumped more than 100 in more than 2 tournaments?
            $bos_cnt = count($group);
            if ($total_dumped >= $thold && $bos_cnt >= $entries_thold) {
                $username = "u_" . cu($uid)->getId() . "_u";
                $this->uh->logTrigger($uid, 'AML3', "Dumped a total of $total_dumped cents in $bos_cnt tournaments. Timestamp of the BoS " . implode(", ", array_column($group, 'updated_at')));
                // We loop the tournament entries that chip dumping has taken place in to find the beneficiaries
                foreach ($group as $beneficiary) {
                    // We get the other entries in the same tournament where that might have benefited
                    $user_beneficiary = phive('SQL')->shs()->loadAssoc("SELECT * FROM tournament_entries WHERE user_id != $uid AND t_id = {$beneficiary['t_id']}");
                    $this->uh->logTrigger($user_beneficiary['user_id'], 'AML3', "Chip dump beneficiary in BoS: {$user_beneficiary['t_id']} finished at: {$user_beneficiary['updated_at']} from user: $username");
                }
            }
        }
    }

    /**
     * AML53 - Customer's Intended Extent of Gambling
     * When a customer's spend is about to exceed the amount selected at registration.
     * @param $user DBUser
     */
    public function customerIntendedExtentOfGambling($user)
    {
        $uid = $user->getId();

        $trigger = 'AML53';

        $intended_gambling_range = $user->getSetting('intended_gambling');

        // If the setting doesn't exist for the player it means that this user is not subject to enforced
        // "intended_gambling", so no check is needed. (Currently this is enforced for DK only)
        if ($intended_gambling_range === false) {
            return;
        }

        // Trigger once a month
        if ($this->uh->hasTriggeredCurrentMonth($user, $trigger)){
            return;
        }
        $percentage = $this->getAndCacheConfig('AML', "$trigger-percentage", '150');
        // Range limits (Ex. 1001-5000, see ranges on intended_gambling.php)
        list($range_from, $range_to) = explode("-", $intended_gambling_range);
        // Values in the range are in unit and not in cents, so we divide by 100.
        $bets_wins = phive('Casino')->getSumsBetsWinsByUserId($uid, date('Y-m-01 00:00:00'), phive()->hisNow());
        $loss = ($bets_wins['sum_bets'] - $bets_wins['sum_wins']) / 100;
        // If the amount of the user loss is greater than $range_to, we add a log
        // Ex. User set range 1001-5000, we log if the loss are greater than 7500
        $ratio = round($loss / $range_to)* 100;
        if ($ratio >= $percentage && !$this->uh->hasTriggeredLastPeriod($uid, $trigger, 1, 'HOUR')) {
            $this->uh->logTrigger($uid, $trigger, "The customer lost ({$loss}), reached the {$ratio}% of the amount selected at registration ({$range_from}-$range_to) .");

        }
    }

    /**
     * Identifies the top depositing customers for the last 7 days
     *
     * @return void
     */
    public function customerIsTopDepositor()
    {
        $trigger = 'AML58';
        $configs = $this->getAndCacheConfig('AML', "{$trigger}-top-depositor", []);

        foreach ($configs as $top_depositor_count => $countries) {
            $top_depositor_count = (int)$top_depositor_count;
            foreach ($countries as $country) {
                $sql = "SELECT uds.user_id, SUM(uds.deposits) AS deposits_sum, uds.currency
                    FROM users_daily_stats uds
                    INNER JOIN users u ON uds.user_id = u.id
                    WHERE uds.date >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),INTERVAL 7 DAY)
                    AND uds.date <= DATE_SUB(CURDATE(), INTERVAL (WEEKDAY(CURDATE()) + 1) DAY)
                    AND u.country = '{$country}'
                    GROUP BY uds.user_id
                    HAVING deposits_sum > 0
                    ORDER BY deposits_sum DESC
                    LIMIT {$top_depositor_count};
                ";
                $data = phive('SQL')->loadArray($sql);

                foreach ($data as $deposit_data) {
                    $amount = nfCents($deposit_data['deposits_sum'], true);
                    $description = "Customer is top depositor with deposit {$amount} {$deposit_data['currency']}";
                    $this->uh->logTrigger(cu($deposit_data['user_id']), $trigger, $description);
                }
            }
        }
    }


    /**
     * AML59 - Compare bonus/rewards to wager ratio
     * Flag customer based on congiguration and ratio
     * @param $user DBUser
     * @return mixed
     */
    public function checkBonusToWagerRatio($user)
    {
        $uid = $user->getId();
        $trigger = 'AML59';

        $thold = $this->getAndCacheConfig('AML', "$trigger-bonus-payout-thold", 100000) / 100;
        $percentage = $this->getAndCacheConfig('AML', "$trigger-bonus-payout-percent", 9);
        $time = $this->getAndCacheConfig('AML', "$trigger-duration-days", 90);
        $months = $time / 30;

        //if percentage is set to zero, flag should be inactive
        if($percentage == 0) {
            return;
        }

        if ($this->uh->hasTriggeredLastPeriod($user, $trigger, $time)) {
            return;
        }

        //convert from cents to notes value and to EUR since base flag is in EUR(1000)
        $rewards = chg($user->getCurrency(), "EUR", $this->getCustomerRewardsByMonths($uid, $months) / 100);
        $wager = chg($user->getCurrency(), "EUR", $this->getCustomerWagerByMonths($uid, $months) / 100);

        if($rewards == 0) {
            return;
        }

        if($rewards > $wager && $rewards >= $thold) {
            $this->uh->logTrigger($uid, $trigger, "The customer has a bonus to wager ratio greater than 100% in the last $months months.");
            $this->setCustomerRiskRating($uid, ['rating_type' => 'AML', 'rating' => 79, 'rating_tag' => 'Medium Risk']);
            return;
        }

        $rewards_to_wager_ratio = ($rewards / $wager) * 100;

        if($rewards >= $thold && $rewards_to_wager_ratio >= $percentage) {
            $this->uh->logTrigger($uid, $trigger, "The customer has a bonus to wager ratio of " . round($rewards_to_wager_ratio, 2) . "% in the last $months months.");
            $this->setCustomerRiskRating($uid, ['rating_type' => 'AML', 'rating' => 79, 'rating_tag' => 'Medium Risk']);

            //Add Temp logger for debugging
            phive('Logger')
                ->getLogger('web_logs')
                ->error(
                    "AML Checks Log",
                    [
                        "threshold" => $thold,
                        "percentage" => $percentage,
                        "time" => $time,
                        "months" => $months,
                        "original_rewards" => $this->getCustomerRewardsByMonths($uid, $months),
                        "original_wager" => $this->getCustomerWagerByMonths($uid, $months), 
                        "converted_rewards" => $rewards,
                        "converted_wager" => $wager, 
                        "rewards_to_wager_ratio" => $rewards_to_wager_ratio

                    ]
            );

            return;
        }
    }

    /**
     * AML62 - flag high-risk behaviour when a user deposits using prepaid cards
     * and withdraws without wagering in any product
     *
     * @param DBUser $user
     *
     * @return void
     */
    public function highRiskBehaviourWithPrepaidCards(DBUser $user): void
    {
        $trigger = "AML62";
        $prepaid_card_type = "P";
        $description = "The customer deposited using prepaid cards and withdraw without wagering";
        $user_id = $user->getId();
        $sql = "SELECT `timestamp`, dep_type, card_hash FROM deposits WHERE user_id = '{$user_id}' ORDER BY `timestamp` DESC LIMIT 1";
        $last_deposit = $this->replica->sh($user_id)->loadArray($sql);

        if (empty($last_deposit[0])) {
            return;
        }

        $sql = "SELECT SUM(amount) FROM bets WHERE user_id = '{$user_id}' AND created_at > '{$last_deposit[0]['timestamp']}'";
        $bets_amount = $this->replica->sh($user_id)->getValue($sql);

        $sql = "SELECT SUM(amount) FROM sport_transactions WHERE user_id = '{$user_id}' AND bet_type = 'bet'
                    AND bet_placed_at > '{$last_deposit[0]['timestamp']}'";
        $sport_bets_amount = $this->replica->sh($user_id)->getValue($sql);

        if ($bets_amount > 0 || $sport_bets_amount > 0) {
            return;
        }
        $psp = phive('Cashier')->getSetting('psp_config_2');
        $pcard_psp = array_keys(array_filter($psp, function ($psp) {
            return $psp['type'] === PSPType::PREPAID_CARD;
        }));

        if (in_array($last_deposit[0]['dep_type'], $pcard_psp, true)) {
            $this->uh->logTrigger($user, $trigger, $description);
            return;
        }

        if (empty($last_deposit[0]['card_hash'])) {
            return;
        }

        $ccard_psps = array_keys(phive('Cashier')->getSetting('ccard_psps'));

        if (in_array($last_deposit[0]['dep_type'], $ccard_psps, true)) {
            $card_type = phive('Cashier/Mts')->getCardType([$last_deposit[0]['card_hash']]);

            if (!empty($card_type) && in_array($prepaid_card_type, $card_type, true)) {
                $this->uh->logTrigger($user, $trigger, $description);
            }
        }
    }

    /**
     * AML64 - flag when a player used an account linked to a different NID than the one used during registration
     *
     * @param DBUser $user
     * @param string $mismatchedNid
     *
     * @return void
     */
    public function nidMismatch(DBUser $user, string $mismatchedNid): void
    {
        $trigger = "AML64";
        $description = "The user attempted to use an account linked to a different NID \"{$mismatchedNid}\" than the one used during registration.";

        $config = phive('Config');
        if ($config->getValue('AML', 'enable-AML64', 'off', ['type' => 'choice', 'values' => ['on','off']]) === 'on') {
            $this->uh->logTrigger($user, $trigger, $description);

            if ($config->getValue('AML', 'AML64-do-user-block', 'off', ['type' => 'choice', 'values' => ['on','off']]) === 'on') {
                $user->depositBlock();
                $user->playBlock();
                $user->withdrawBlock();
            }
        }
    }

    /**
     * Set customer risk rating
     * @param int $user_id
     * @param array $attributes
     * @return void
     */
    public function setCustomerRiskRating($user_id, $attributes)
    {
        $rating_log = [
            'rating_type' => $attributes['rating_type'],
            'rating' => $attributes['rating'],
            'rating_tag' => $attributes['rating_tag'],
            'user_id' => $user_id
        ];
        phive('SQL')->sh($user_id)->insertArray('risk_profile_rating_log', $rating_log);

    }

    /**
     * Get user wager by number of months
     *
     * @param int $user_id
     * @param int $months
     * @return mixed
     */
    public function getCustomerWagerByMonths($user_id, $months=3)
    {
        list($nowFormatted, $previousMonthDateFormatted) = phive()->getLastNMonthsFromNow($months);

        // Get wager sum from users_daily_stats for previous days
        $historicalWagerQuery = "SELECT SUM(bets) as wagerSum
         FROM `users_daily_stats`
         WHERE user_id = $user_id
         AND `date` between '$previousMonthDateFormatted' and '$nowFormatted' ";

         $historicalWager = phive('SQL')->sh($user_id)->loadAssoc($historicalWagerQuery);
         $historicalWagerSum = $historicalWager['wagerSum'] ?? 0;
      

        // Get today's wager from bets table
        $todayStartFormatted = (new DateTime('today', new DateTimeZone('Europe/Malta')))->format('Y-m-d 00:00:00');
        
        $todayWagerQuery = "SELECT SUM(amount) as todayWagerSum
            FROM `bets`
            WHERE user_id = $user_id
            AND created_at BETWEEN '$todayStartFormatted' AND '$nowFormatted'";
        
        $todayWager = phive('SQL')->sh($user_id)->loadAssoc($todayWagerQuery);
        $todayWagerSum = $todayWager['todayWagerSum'] ?? 0;
        
        // Combine historical and today's wager
        $totalWagerSum = $historicalWagerSum + $todayWagerSum;
        
        return $totalWagerSum;

    }

    /**
     * Get user rewards by number of months
     *
     * @param int $user_id
     * @param int $months
     * @return mixed
     */
    public function getCustomerRewardsByMonths($user_id, $months=3)
    {
        list($nowFormatted, $previousMonthDateFormatted) = phive()->getLastNMonthsFromNow($months);

        $rewards = "
        SELECT
            SUM(ABS(ct.amount)) AS all_sum
        FROM cash_transactions ct
        WHERE ct.user_id = {$user_id}
        AND ct.timestamp >= '$previousMonthDateFormatted'
        AND ct.timestamp <= '$nowFormatted'
        AND transactiontype IN (
            14,32,31,51,66,69,74,77,80,82,84,85,86
        )";

        $rewards = phive('SQL')->sh($user_id)->loadAssoc($rewards);
        $rewards = $rewards['all_sum'];

        $failed = "
        SELECT
            SUM(ABS(ct.amount)) AS all_sum
        FROM cash_transactions ct
        LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
        WHERE ct.user_id = {$user_id}
        AND ct.timestamp >= '$previousMonthDateFormatted'
        AND ct.timestamp <= '$nowFormatted'
        AND (
            ct.transactiontype IN (
                53,67,72,75,78,81
            ) OR (
                ct.transactiontype = 15 AND (
                    bt.bonus_type != 'freespin' OR
                    bt.bonus_type != 'casinowager' OR
                    bt.bonus_type IS NULL
                )
            )
        )";

        $failed = phive('SQL')->sh($user_id)->loadAssoc($failed);
        $failed = $failed['all_sum'];

        $rewards -= (int)$failed;
        return $rewards;

    }
}
