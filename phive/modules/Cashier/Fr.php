<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once('Arf.php');
use MaxMind\MinFraud;

class Fr extends Arf
{
    public function __construct()
    {
        parent::__construct();
    }

    /* MAIN METHODS */

    /**
     *
     * @param int $user_id
     */
    public function onRegistration($user_id)
    {
        $user = cu($user_id);
        $this->nameOrSurnameInEmail($user);
        $this->addUserToExternalKycMonitoring($user);

    }

    public function onLogin($uid)
    {
        $user = cu($uid);

        //$ip              = $user->getAttr('cur_ip');
        $current_session = $user->getCurrentSession();
        $ip = $current_session['ip'];
        $cur_fingerprint = $current_session['fingerprint'];

        if (!empty($current_session['otp'])) { //Validate all previous like we do with deposits TODO find a way to this only once there is a validation not to all logins
            phive('SQL')->sh($user)->query("
                UPDATE users_sessions SET otp = 1 WHERE user_id = {$user->getId()} AND otp = 0 AND (ip = '{$ip}' OR fingerprint = '{$cur_fingerprint}')
            ");
        }

        /**
         * AML2 - PEP & SL Failure
         * where PEP & SL checks = failure
         *
         * Special Note:
         * The trigger is fired from a call to "onKycCheck" from one of functions being processed during lic("checkKyc") when the checks fail
         * (Take note that from this function it will be fired with $recurrent = true, so "experian_block" will not be set on the user)
         */
        $this->pepRegularCheck($user);

        /**
         * AML32 - IP links
         * > or = 5 IP linked accounts from sign up
         */
        $this->ipLinks($user);

        /**
         * AML35 - Account compromised Fraud
         * successful account login deriving from new device and IP which is not within geo LOC or Player History
         * We get the other sessions that are not from the same device and IP.
         */
        $sql_str      = "SELECT COUNT(*) FROM users_sessions WHERE id != {$current_session['id']} AND user_id = $uid";

        $ip_count     = phive('SQL')->sh($uid, '', 'users_sessions')->getValue($sql_str." AND ip = '$ip' AND ip != ''");
        $fprint_count = phive('SQL')->sh($uid, '', 'users_sessions')->getValue($sql_str." AND fingerprint = '{$current_session['fingerprint']}' AND fingerprint != ''");
        $msg          = '';
        $cur_country = phive('IpBlock')->getCountry($current_session['ip']);
        // IP and device does not exist in history in any way, either on the same row or on separate rows and  IP is not from the same country as player
        if((empty($ip_count) && empty($fprint_count))   &&  ($cur_country != $user->getCountry()) && (!empty($cur_fingerprint) && !empty($ip))   ){
            $msg .= "$ip and {$current_session['fingerprint']} does not exist in history.  {$ip} is from {$cur_country} which is not {$user->getCountry()}";
        }


        if(!empty($msg))
            $this->uh->logTrigger($uid, 'AML35', $msg);

        /**
         * AML36 - Brute Force Attack
         * Multiple successful simultaneous login exceeding more than 3 accounts deriving from the same device and IP
         * We do the last X hours and use device fingerprint and IP in combination to try to avoid false positives (several people playing in a big office)
         */
        $hours_thold     = $this->getAndCacheConfig('AML', 'AML36-hours', 10);
        $acc_thold       = $this->getAndCacheConfig('AML', 'AML36-num-accounts', 3);
        $sstamp          = phive()->hisMod("-$hours_thold hour");
        // We get the other sessions

        $sql = "SELECT * FROM users_sessions
                INNER JOIN users ON users.id = users_sessions.user_id
                WHERE users.active = 0
                AND ((fingerprint = '$cur_fingerprint' AND fingerprint != '') AND (ip = '$ip' AND ip != ''))
                AND users_sessions.created_at >= '$sstamp'
                AND users.id != '$uid'
                GROUP BY users_sessions.user_id";

        $sessions = phive('SQL')->shs('merge', '', null, 'users_sessions')->loadArray($sql);

        $sess_count = count($sessions);
        // We have more than the allowed amount of different logins
        if($sess_count > $acc_thold){
            $sUid = '';
            $aUid = [];
            foreach($sessions as $sess){
                $sUid .= "u_" . cu($sess['user_id'])->getUsername() . "_u";
                $aUid[] = "u_" . cu($sess['user_id'])->getUsername() . "_u";
            }
            if (!empty($aUid)) {
                $extra = json_encode($aUid);
                $this->uh->logTrigger($uid, 'AML36', "{$user->getUsername()}, sid:{$current_session['id']} has same IP({$ip})  and device fingerprint({$cur_fingerprint}) as {$sUid}, sid: {$sess['id']}", true, true, '', $extra);
            }

        }
        /* AML41 */
        $this->checkBonusAbuse($uid, $current_session);
    }

    // Run just after the current deposit has been saved, in a forked process.
    public function onDeposit($uid, $dep_id, $timestamp = '')
    {
        $user = cu($uid);

        $time = empty($timestamp) ? time() : $timestamp;

        $hours_since_reg = phive()->subtractTimes($time, strtotime($user->data['register_date']), 'h');

        $extra_where = empty($timestamp) ? $timestamp : " AND timestamp < '{$timestamp}'";

        if ($hours_since_reg < 48) {
            //AML24
            $this->transactionBetweenThreshold($user, $extra_where);
        }
        if ($hours_since_reg < 72) {
            //AML18
            $this->multipleCardAlert($user, $user->data['register_date'] .' 00:00:00', $timestamp);
            //AML22
            $this->suspiciousDepositPattern($user, $extra_where);
        }

        //AML21 - AML25
        $this->checkPreviousDeposits($user, $extra_where);

        //AML37
        $this->checkDepositWagerRatio($user);

        //AML47
        // We check if we've got a settled chargeback
        $settled_chargeback = $this->replica->sh($uid, '', 'cash_transactions')->loadAssoc("SELECT * FROM cash_transactions WHERE transactiontype = 92 AND user_id = $uid");
        if(!empty($settled_chargeback)){
            $types = ['instadebit', 'wirecard', 'emp', 'citadel', 'giropay', 'sofort'];
            $new_deposit = $this->replica->sh($uid, '', 'deposits')->loadAssoc("SELECT * FROM deposits WHERE id = $dep_id");
            if(in_array($new_deposit['dep_type'], $types)){
                // We've got a new deposit with one of the liable methods
                $types_str = $this->replica->makeIn($types);
                $old_deps = $this->replica->sh($uid, '', 'deposits')->loadArray("SELECT * FROM deposits WHERE user_id = $uid AND id != $dep_id AND dep_type IN($types_str) GROUP BY dep_type", 'ASSOC', 'dep_type');
                if(!in_array($new_deposit['dep_type'], array_keys($old_deps))){
                    // The new PSP has not been used before
                    $old_deps_str = implode(',', array_keys($old_deps));
                    $this->uh->logTrigger($user, 'AML47', "Chargeback settlement id: {$settled_chargeback['id']} and {$new_deposit['dep_type']} is not in $old_deps_str");
                }
            }
        }
        $this->passwordLinks($user);
    }

    /**
     * @param $uid
     */
    public function onFailedDeposit($uid)
    {
        $user = cu($uid);

        $pre_failed_dep = phive('SQL')->sh($uid, '', 'failed_transactions')
            ->loadArray("SELECT * FROM failed_transactions WHERE type = 0 AND user_id = {$uid} ORDER BY created_at DESC LIMIT 3");

        //AML27 Retained card
        if (!empty($pre_failed_dep[0]) && $pre_failed_dep[0]['error_code'] == MtsError::CardStolen) {
            $this->uh->logTrigger($user, 'AML27', "Card {$pre_failed_dep[0]['account']} reported stolen.");
        }



        // AML29 3DS failed deposit followed by CVV failure decline deposit
        if (count($pre_failed_dep) > 1) {
            if ($pre_failed_dep[0]['error_code'] == MtsError::CvcWrong
                && $pre_failed_dep[1]['error_code'] == MtsError::CardHolderFailedOrCancelled
            ) {
                $this->uh->logTrigger($user, 'AML29', "Card used: {$pre_failed_dep[0]['account']}, amount: {$pre_failed_dep[0]['amount']} cents {$pre_failed_dep[0]['currency']}");
            }
        }


    }

    public function onFirstDeposit($uid)
    {
        $user = cu($uid);
        $uid = $user->getId();
        //AML26 GBP accounts with a first deposit between £251 and £299
        if ($user->data['currency'] == 'GBP') {
            $first_deposit = $this->replica->sh($uid, '', 'first_deposits')->loadArray("SELECT * FROM first_deposits WHERE user_id = {$uid} ORDER BY timestamp,id ASC")[0];
            $amount = (int)$first_deposit['amount'];
            $thold_frd9 = phive('Config')->valAsArray('AML', 'AML26', ' ', ':', 'min:25100 max:29900');
            if ($amount >= $thold_frd9['min'] && $amount <= $thold_frd9['max']) {
                $this->uh->logTrigger($user, 'AML26', "Method: {$first_deposit['dep_type']}, amount: {$first_deposit['amount']} cents  {$first_deposit['currency']}");
            }
        }

        $this->checkForSuspiciousEmail($user);
    }

    /*
     * AML38-AML39-AML40
     */
    public function onChargeback($uid, $method = '', $amount = 0, $currency = '')
    {
        $methods_map = [
            'instadebit' => 'AML38',
            'citadel' => 'AML39',
            'wirecard' => 'AML40'
        ];


        if (isset($methods_map[$method])) {

            $this->uh->logTrigger($uid, $methods_map[$method], "Chargeback through $method of $amount cents, currency: $currency");
        }
    }


    /* END MAIN METHODS */
    /**
     * AML48
     * Monitor and trigger on accounts where API will inform us when the Instadebit details associated to the account do not match to that of the player.
     *
     * 2020-04-16.New Requirement: if last name is correct, then flag should not trigger
     *
     * @param DBUser $user
     * @param array $account_info Array with account information retrieve from instadebit BO - see "instadebit_data" on users_settings
     * @return bool
     */
    public function instadebitFullnameMismatch(DBUser $user, array $account_info)
    {
        $trigger = 'AML48';

        $instadebit_firstname = removeSpecialCharacter($account_info['firstname']);
        $instadebit_lastname = removeSpecialCharacter($account_info['lastname']);
        $db_firstname = removeSpecialCharacter($user->data['firstname']);
        $db_lastname = removeSpecialCharacter($user->data['lastname']);

        // less strict check applied for now, if lastname match we consider it safe.
        if (strpos($instadebit_lastname, $db_lastname) !== false) {
            return false;
        }

        // if we want to be more strict and compare both first and last name we can comment the above IF.
        if (strpos($instadebit_firstname, $db_firstname) !== false && strpos($instadebit_lastname, $db_lastname) !== false) {
            return false;
        }

        $message = "Instadebit full name mismatch, received {$instadebit_firstname} {$instadebit_lastname} on the deposit notification for user with full name {$user->getFullName()}";
        $user->addComment("{$message} //system message", 0, 'amlfraud');
        $this->uh->logTrigger($user, $trigger, $message);

        return true;
    }

    /*
     * AML20 Country of Card does not match Registered IP and Country of Player
     */
    public function checkCardCountry($user, $card_country, $card_id)
    {
        if(empty($card_id)){
            return null;
        }

        $customer_country = $user->getAttribute('country');
        if ($customer_country != $card_country) {
            $uid             = $user->getId();
            $already_trigger = phive('SQL')->sh($uid)->loadAssoc("SELECT id FROM triggers_log WHERE user_id = $uid AND trigger_name = 'AML20' AND txt = '$card_id' LIMIT 1");
            if (empty($already_trigger)){
                $this->uh->logTrigger($user, 'AML20', "Customer current country: {$customer_country}, card country: {$card_country}, card_id: {$card_id}", true, true, '', '', $card_id);
            }
        }
    }

    /*
     * AML22 Accounts not older than 72 hours depositing in either descending or ascending order
     */
    public function suspiciousDepositPattern($user, $extra_where = '')
    {
        $res = phive('SQL')->sh($user->getId(), '', 'deposits')->loadArray("SELECT amount FROM deposits WHERE user_id = {$user->getId()} {$extra_where} ORDER BY timestamp ASC");

        if (count($res) <= 1) {
            return false;
        }
        $previous = array_shift($res);
        $asc = $desc = 0;
        foreach ($res as $dep) {
            if ($dep['amount'] < $previous['amount']) {
                $desc++;
            } elseif ($dep['amount'] > $previous['amount']) {
                $asc++;
            }
            $previous = $dep;
        }

        if (count($res) == $desc || count($res) == $asc) {
            $this->uh->logTrigger($user, 'AML22', "Accounts not older than 72 hours depositing in either descending or ascending order");
            return true;
        }

        return false;
    }

    /*
     * AML32 > or = 5 IP linked accounts from sign up
     */
    public function ipLinks($user)
    {
        $trigger = 'AML32';
        $thold = $this->getAndCacheConfig('AML', $trigger, 5);
        $reg_ip = $user->getAttribute('reg_ip');

        if(!empty($reg_ip) && $reg_ip != 'unknown' && $reg_ip != '127.0.0.1') {
            $res = $this->replica->shs()
                ->loadArray(
                    "SELECT u.id FROM users u WHERE reg_ip = '{$reg_ip}'
                        AND u.id != {$user->getId()}
                        AND NOT EXISTS (
                            SELECT user_id FROM users_settings AS u_s
                            WHERE u.id = u_s.user_id
                              AND (
                                  (u_s.setting = 'registration_in_progress' AND u_s.value >= 1)
                                      OR (u_s.setting = 'test_account' AND u_s.value = 1)
                                  )
                        ) ORDER BY u.register_date DESC LIMIT $thold");
            $slice_res = array_slice($res, 0, $thold, true);
            $countUsers = count($slice_res);
            if ($countUsers >= $thold) {
                $usernames = [];
                foreach ($slice_res as $uid) {
                    $usernames[] = "u_". cu($uid)->getUsername(). "_u";
                }
                $extra = json_encode($usernames);
                $usernames = implode(" ", $usernames);

                if ($this->uh->hasTriggeredLastPeriod($uid, $trigger, 1, 'HOUR')) {
                    return;
                }
                $this->uh->logTrigger($user, $trigger, "The IP {$reg_ip} has been used by {$countUsers} customers: $usernames", true, true, '', $extra);
            }
        }
    }

    private function getBetCountInPeriod($user_id, $start, $end) {
        return $this->replica->sh($user_id, '', 'bets')
            ->getValue("SELECT count(*) FROM bets WHERE user_id = {$user_id} AND created_at BETWEEN '{$start}' AND '{$end}'");
    }

    /*
     * AML21 Velocity Deposits < or =  5 minutes apart or less between each successful transaction no bets
     * AML25 Deposit between €10 and €25 via Skrill or Neteller <= 24 hours after last Card deposit
     */
    public function checkPreviousDeposits($user, $extra_where = '')
    {
        $uid = $user->getId();
        $previous_dep = $this->replica->sh($uid, '', 'deposits')->loadArray("SELECT * FROM deposits WHERE user_id = {$uid} {$extra_where} ORDER BY timestamp DESC LIMIT 2");

        $trigger = 'AML21';
        $thold_4 = $this->getAndCacheConfig('AML', $trigger, 5);
        $min = phive()->subtractTimes(strtotime($previous_dep[0]['timestamp']), strtotime($previous_dep[1]['timestamp']), 'm');
        if ($min <= $thold_4) {
            if ($this->getBetCountInPeriod($uid, $previous_dep[1]['timestamp'], $previous_dep[0]['timestamp']) == 0) {
                $amount_both = $previous_dep[0]['amount'] + $previous_dep[1]['amount'];
                if (!$this->uh->hasTriggeredLastPeriod($uid, $trigger, 1, 'HOUR')) {
                    $this->uh->logTrigger($user, $trigger, "{$min} between deposits, amount in total {$amount_both} cents");
                }
            }
        }

        //AML25
        $thold_8 = phive('Config')->valAsArray('AML', 'AML25', ' ', ':', 'min:1000 max:2500 check_methods:skrill,neteller card_methods:wirecard,emp');
        $hours = phive()->subtractTimes(strtotime($previous_dep[0]['timestamp']), strtotime($previous_dep[1]['timestamp']), 'h');

        if (in_array($previous_dep[0]['dep_type'], explode(',', $thold_8['check_methods']))
            && $previous_dep[0]['amount'] >= chg($this->def_cur, $user, $thold_8['min'], 1)
            && $previous_dep[0]['amount'] <= chg($this->def_cur, $user, $thold_8['max'], 1)
            && in_array($previous_dep[1]['dep_type'], explode(',', $thold_8['card_methods']))
            && $hours <= 24
        ) {
            $this->uh->logTrigger($user, 'AML25', "Method: {$previous_dep[0]['dep_type']}, amount: {$previous_dep[0]['amount']} cents {$previous_dep[0]['currency']}");
        }
    }

    /*
     * AML24 Deposit between €5000 - €10,000 transaction or more within the first 48 hours of opening
     */
    public function transactionBetweenThreshold($user, $extra_where = '')
    {
        $thold = phive('Config')->valAsArray('AML', 'AML24', ' ', ':', 'min:500000 max:1000000');
        $min = chg($this->def_cur, $user, $thold['min'], 1);
        $max = chg($this->def_cur, $user, $thold['max'], 1);
        $res = $this->replica->sh($user->getId(), '', 'deposits')
            ->loadAssoc("
                SELECT COUNT(*) AS count, SUM(amount) AS sum, currency
                FROM deposits
                WHERE amount BETWEEN {$min} AND {$max}
                  AND status = 'approved'
                  {$extra_where}
                  AND user_id = {$user->getId()}
            ");
        if ($res['count'] >= 1) {
            $sum = mc($res['sum'], $res['currency'], 'div');
            $amount_prettified = nfCents($sum, true);
            $this->uh->logTrigger(
                $user,
                'AML24',
                "Customer deposited €{$amount_prettified} within 48 hours from sign up."
            );
        }
    }


    /**
     * AML18 > or = 3 or more cards added within 24 - 72 hours from sign up
     *
     * @param DBUser $user
     * @param string $start_date
     * @param string $end_date
     */
    public function multipleCardAlert($user, $start_date = '', $end_date = '')
    {
        $trigger = 'AML18';
        if (!empty($this->uh->getNewestTrigger($user->getId(), $trigger))) {
            return;
        }
        $thold = $this->getAndCacheConfig('AML', $trigger, 3);

        try {
            $res = phive('Cashier/Mts')->arf('getCardsCount', [$user->getId(), $start_date, $end_date])['card_count'];
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        if ($res >= $thold)
            $this->uh->logTrigger($user, $trigger, "Has used $res cards");
    }

    //https://github.com/maxmind/minfraud-api-php
    /*
       $ev_type can be:

       account_creation
       account_login
       email_change
       password_reset
       purchase
       recurring_purchase
       referral
       survey

       phive('Cashier/Aml')->minFraud('dgdfgfdg', 'purchase', [
          'amount'   => 1000,
          'dep_type' => 'wirecard',
          'status'   => 'approved',
          'ccard_info' => [
              'card_num'  => '5656',
              'exp_year'  => 56,
              'exp_month' => 5,
              'tree_d'    => 1,
              'prepaid'   => 0
          ]
       ]);
       phive('Cashier/Aml')->minFraud(5196335, 'survey');
     */

    function minFraud($uid, $ev_type = '', $event_data = [], $call_type = 'score')
    {
        $u = cu($uid);
        $ud = $u->data;
        $ss = phive('IpBlock')->allSettings();
        $mf = new MinFraud($ss['maxmind_uid'], $ss['maxmind_key']);
        $session = $u->getCurrentSession();

        $full_domain = phive()->getSetting('full_domain');

        $tr_id = in_array($ev_type, ['purchase', 'recurring_purchase']) ? $event_data['id'] : '';

        list($calling_code, $number_without_calling_code, $number) = phive('Mosms')->splitNumberIntoParts($u);

        $cur_accept_language = phMgetShard('current-accept-language', $uid);
        $cur_user_agent      = phMgetShard('current-user-agent', $uid);

        $device_info = [
            'ip_address'      => $session['ip'] == '127.0.0.1' ? '195.158.92.198' : $session['ip'], //If localhost use the office IP to get around the error throwing
            'session_age'     => time() - strtotime($session['created_at']),
            'session_id'      => $full_domain . $session['id'],
            'user_agent'      => empty($cur_user_agent) ? $session['fingerprint'] : $cur_user_agent,
            'accept_language' => empty($cur_accept_language) ? $ud['preferred_lang'] : $cur_accept_language
        ];

        $date = phive()->today();
        $his = phive()->hisNow('', 'H:i:s');
        $event_info = [
            'transaction_id' => $full_domain . $tr_id,
            'shop_id' => $full_domain,
            'time' => "{$date}T{$his}+00:00",
            'type' => $ev_type,
        ];

        $billing_info = [
            'first_name' => $ud['firstname'],
            'last_name' => $ud['lastname'],
            //'company'            => '',
            'address' => $ud['address'],
            //'address_2'          => '',
            'city' => $ud['city'],
            //'region'             => '',
            'country' => $ud['country'],
            'postal' => $ud['zipcode'],
            'phone_number' => $number_without_calling_code,
            'phone_country_code' => $calling_code,
        ];

        try{
            $request = $mf->withDevice(
                $device_info
            )->withEvent(
                $event_info
            )->withAccount([
                'user_id' => $u->getId(),
                'username_md5' => md5($ud['username']),
            ])->withEmail([
                'address' => $ud['email'],
                'domain' => explode('@', $ud['email'])[1],
            ])->withBilling(
                $billing_info
            );
        }catch(Exception $e){
            error_log($e->getMessage());
        }

        if(empty($request)){
            return false;
        }

        if (in_array($ev_type, ['purchase', 'recurring_purchase'])) {

            // TODO, support declined with decline code?

            $payment_options = [
                'processor' => 'other', //$event_data['dep_type'],
                'was_authorized' => $event_data['status'] == 'approved',
                'decline_code' => (string)$event_data['declice_code']
            ];

            $order_data = [
                'amount' => phive()->twoDec($event_data['amount']),
                'currency' => $ud['currency']
                //'referrer_uri'     => 'http://www.amazon.com/',
            ];

            if (!empty($ud['bonus_code']))
                $order_data['affiliate_id'] = $ud['bonus_code'];

            $mf->withPayment($payment_options)->withOrder($order_data);

            // For token to work properly the card info needs to contain all fields we have in the MTS table.
            if (!empty($event_data['ccard_info'])) {
                $card_num = str_replace(' ', '', $event_data['ccard_info']['card_num']);
                $issuer_id = substr($card_num, 0, 6);
                $last_four = substr($card_num, -4);
                $token = md5(implode('', $event_data['ccard_info']));

                $ccard_data = [
                    'issuer_id_number' => $issuer_id,
                    'last_4_digits' => $last_four,
                    'token' => $token
                    //'bank_name'               => '',
                    //'bank_phone_country_code' => '',
                    //'bank_phone_number'       => '',
                    //'avs_result'              => '',
                    //'cvv_result'              => '',
                ];

                $mf->withCreditCard($ccard_data);
            }
        }


        // To get the minFraud Score response model, use ->score():
        if($call_type == 'score'){
            $scoreResponse = $request->score();
            return $scoreResponse->riskScore;
        }

        //foreach ($scoreResponse->warnings as $warning) {
        //    print($warning->warning . "\n");
        //}

        if($call_type == 'factors'){
            $factorsResponse = $request->factors();
            return $factorsResponse;
        }

        if($call_type == 'insights'){
            return $request->insights();
        }

    }

    /**
     * AML44
     * Checks if the email address contains the first name or the last name of the user
     *
     * @param object $user The DBUser object
     * @return boolean  True if the flag has been set
     */
    public function nameOrSurnameInEmail($user)
    {
        $trigger    = 'AML44';
        $firstname  = strtolower($user->getAttribute('firstname'));
        $lastname   = strtolower($user->getAttribute('lastname'));
        $email      = strtolower($user->getAttribute('email'));

        if (strpos($email, $firstname) !== false && strpos($email, $lastname) !== false) {
            $this->uh->logTrigger($user, $trigger, "email: $email");
            return true;
        }

        return false;
    }

    /**
     * AML31
     * This has to be called directly, because we don't save the user if the verification code is not valid.
     * This will only be called when the flag needs to be set.
     *
     */
    public function emailAndPhoneCheck($uid)
    {
        $trigger = 'AML31';
        $user = cu($uid);
        $email = $user->getAttribute('email');
        $phone = $user->getAttribute('mobile');
        $this->uh->logTrigger($user, $trigger,"email:{$email} phone:{$phone}");
        return true;
    }

    /**
     * AML30
     */
    public function checkForSuspiciousEmail($user): void
    {
        $userId = $user->getId();
        $daysThreshold = $this->getAndCacheConfig('AML', 'AML30-email-age-thold', 180);
        $emailAgeThreshold = phive()->modDate('', "-$daysThreshold day");
        $res = $this->minFraud($userId, 'survey', [], 'insights');
        $user->setSetting('minfraud-risk-score', $res->riskScore);
        $user->setSetting('minfraud-result', json_encode($res));

        if (!empty($res->email->isHighRisk)) {
            $this->uh->logTrigger($userId, 'AML30', "{$user->getAttr('email')} is flagged as high risk by minFraud");
            $user->setSetting('suspicious-email-fraud-flag', 1);
            return;
        }

        // Email and email domain are newer than the threshold
        if ($res->email->firstSeen > $emailAgeThreshold && $res->email->domain->firstSeen > $emailAgeThreshold) {
            $this->uh->logTrigger($userId, 'AML30', "{$user->getAttr('email')} newer than $emailAgeThreshold");
            $user->setSetting('suspicious-email-fraud-flag', 1);
            return;
        }

        // Email is temporary
        if ($res->email->isDisposable === true) {
            $this->uh->logTrigger($userId, 'AML30', "{$user->getAttr('email')} is a disposable temporary email");
            $user->setSetting('suspicious-email-fraud-flag', 1);
        }
    }

    /**
     * AML37
     * Customers who has deposited €10,000+ and has only wagered between 0x-2x their deposited amount.
     * If triggered play, deposit, withdrawal blocks will be applied.
     *
     * @param DBUser $user
     * @return bool
     */
    public function checkDepositWagerRatio($user)
    {
        $trigger = 'AML37';

        // already triggered - prevent unnecessary users_daily_stats query
        if (!empty($this->uh->getNewestTrigger($user, $trigger))) {
            return false;
        }

        $result = phive('SQL')->sh($user)->loadArray("
            SELECT IFNULL(SUM(bets / multiplier), 0) as bets, IFNULL(SUM(deposits / multiplier), 0) as deposits
            FROM users_daily_stats as us
               INNER JOIN fx_rates AS fx ON fx.code = us.currency AND us.date = fx.day_date
            WHERE user_id = {$user->getId()};
        ")[0];

        $today_deposits = phive('SQL')->sh($user)->loadArray("
            SELECT IFNULL(SUM(amount / multiplier), 0) AS deposits
            FROM deposits
                     INNER JOIN fx_rates ON fx_rates.code = deposits.currency
                        AND fx_rates.day_date = DATE(deposits.timestamp)
            WHERE user_id = {$user->getId()}
              AND deposits.timestamp >= CURDATE()
              AND deposits.timestamp < CURDATE() + INTERVAL 1 DAY;
        ")[0];

        $today_bets = phive('SQL')->sh($user)->loadArray("
            SELECT (bets / multiplier) AS bets
            FROM users_realtime_stats
                     INNER JOIN fx_rates ON fx_rates.code COLLATE utf8_unicode_ci = users_realtime_stats.currency
                        AND fx_rates.day_date = users_realtime_stats.date
            WHERE user_id = {$user->getId()}
              AND date = CURDATE();
        ")[0];

        $deposits = $result['deposits'] + $today_deposits['deposits'];
        $bets = $result['bets'] + $today_bets['bets'];

        // user deposited less than $trigger-eur-cents
        if ($deposits < phive('Config')->getValue('AML', "$trigger-thold-eur-cents", 1000000)) {
            return false;
        }

        // user wagered more than 2x
        $ratio = number_format($bets / $deposits, 2);
        if ($ratio > 2) {
            return false;
        }

        $sports_past = phive('SQL')->sh($user)->loadArray("
            SELECT IFNULL(SUM(bets / multiplier), 0) AS bets
            FROM users_daily_stats_sports AS us
                     INNER JOIN fx_rates AS fx ON fx.code COLLATE utf8_unicode_ci = us.currency
                        AND us.date = fx.day_date
            WHERE user_id = {$user->getId()};
        ")[0]['bets'];

        $sports_current_day = phive('SQL')->sh($user)->loadArray("
            SELECT IFNULL(SUM(st.amount / fr.multiplier), 0) AS bets
            FROM sport_transactions st
                     INNER JOIN fx_rates fr ON fr.code COLLATE utf8_unicode_ci = st.currency
                        AND fr.day_date = DATE(st.created_at)
            WHERE st.user_id = {$user->getId()}
              AND st.bet_type = 'bet'
              AND st.created_at >= CURDATE()
              AND st.created_at < CURDATE() + INTERVAL 1 DAY;
        ")[0]['bets'];

        $sport_bets_sum = $sports_past + $sports_current_day;

        // user wagered more than 1x on sports
        $sports_ratio = number_format($sport_bets_sum / $deposits, 2);
        if ($sports_ratio > 1) {
            return false;
        }

        $user->depositBlock();
        $user->playBlock();
        $user->withdrawBlock();
        $this->uh->logTrigger($user, $trigger, "Wager vs deposit ratio is $ratio");

        return true;
    }

    /**
     * AML46
     * Checks if the password has been used by 3 or more other users
     *
     * @param object $user The DBUser object
     * @return boolean  True if the flag has been set
     */
    public function passwordLinks($user)
    {
        $trigger       = 'AML46';
        $password_hash = $user->getAttribute('password');

        $sql = "SELECT
                    username
                FROM
                    users
                WHERE
                    password = '{$password_hash}'";

        $result = phive('SQL')->shs('merge', '', null, 'users')->loadArray($sql);

        if(is_array($result) && count($result) > 3) {
            /*if (count($result) > 10) {
                $result = array_slice($result, 0, 10);
            }*/
            $totUsers = count($result);
            $usernames = [];
            foreach ($result as $u) {
                $usernames[] = "u_" . $u['username'] . '_u ';
            }
            $extra = json_encode($usernames);
            $this->uh->logTrigger($user, $trigger, "Users with the same password: {$totUsers}",true,true,'',$extra);
            return true;
        }

        return false;
    }

    /**
     * Remove flag AML31
     *
     * @param int $user_id
     * @return boolean
     */
    public function removeEmailAndPhoneCheckFlag($user_id)
    {
        $trigger = 'AML31';
        $user = cu($user_id);
        $this->uh->removeTrigger($user, $trigger);
        return true;
    }


    function everydayCron($day = ''){
        error_log("FR1". date('Y-m-d H:i:s'));

        // AML45
        // Recorded profit of more than 200% exceeding €5,000 of last deposit within 24 hours
        $percent_thold = $this->getAndCacheConfig('AML', 'AML45-percent', 200) / 100;
        $money_thold   = $this->getAndCacheConfig('AML', 'AML45-money', 1000000);
        $hours_thold   = $this->getAndCacheConfig('AML', 'AML45-hours', 24);

        if (empty($day)) {
            $sstamp        = phive()->hisMod("-$hours_thold hour");
            $where = "timestamp >= '$sstamp'";
        } else {
            $day_time = $day .' 23:59:59';
            $sstamp = phive()->hisMod("-$hours_thold hour", $day_time);
            $where = "timestamp BETWEEN '{$sstamp}' AND '{$day_time}' ";
        }

        // We get the newest deposit made within the last 24 hours by each player
        $str = "SELECT d1.* FROM deposits d1
                INNER JOIN
                (
                    SELECT max(id) AS newest_id, user_id
                    FROM deposits
                    WHERE status = 'approved'
                    AND {$where}
                    GROUP BY user_id
                ) AS d2 ON d1.user_id = d2.user_id AND d1.id = d2.newest_id
                WHERE d1.status = 'approved'
                AND d1.{$where}
                ORDER BY d1.timestamp DESC";

        $deposits = phive('SQL')->shs('merge', '', null, 'deposits')->loadArray($str);

        foreach($deposits as $d){
            $profit = phive('SQL')->sh($d, 'user_id', 'users_game_sessions')->getValue("SELECT SUM(result_amount) FROM users_game_sessions WHERE user_id = {$d['user_id']} AND start_time >= '{$d['timestamp']}'");
            // Is the profit lower than the equivalent of 5k EUR? If so we continue.
            if(chg($d['currency'], $this->def_cur, $profit) < $money_thold)
                continue;
            // Profit is positive and larger than 2 times the last deposit amount.
            if($profit > 0 && $profit / $d['amount'] > $percent_thold){
                $this->uh->logTrigger($d['user_id'], 'AML45', "Deposit: {$d['amount']} at {$d['timestamp']}, profit amount: $profit");
            }
        }


        // AML42
        $percent_thold = $this->getAndCacheConfig('AML', 'AML42-percent', 5) / 100;
        $days_thold    = $this->getAndCacheConfig('AML', 'AML42-days', 7);
        $count_thold   = $this->getAndCacheConfig('AML', 'AML42-consecutive-days',3); // TODO missing config on DB + "AML42-prof-session-count" config exist and not used


        if (empty($day)) {
            $sstamp        = phive()->hisMod("-$days_thold day");
            $where = "start_time > '$sstamp'";
        } else {
            $day_time = $day .' 23:59:59';
            $sstamp = phive()->hisMod("-$hours_thold hour", $day_time);
            $where = "start_time BETWEEN '{$sstamp}' AND '{$day_time}' ";
        }

        $sessions      = phive('SQL')->shs('merge', '', null, 'users_game_sessions')->loadArray("SELECT * , date(start_time) as date FROM users_game_sessions WHERE {$where} AND bet_amount > 0   ");

        $grouped_user = phive()->group2d($sessions, 'user_id');

        // group games together
        foreach ($grouped_user as $user => $user_data) {
            $day_grouped = phive()->group2d($user_data, 'date');
            $prof_day_cnt = 0;
            $uid = "";
            // group each game by day
            foreach ($day_grouped as $day_key => $day_data) {
                $uid = $day_data[0]['user_id'];
                $gamePlayed = $day_data[0]['game_ref'];
                $game = phive('MicroGames')->getByGameRef($gamePlayed);

                $summed = phive()->sum2d($day_data);
                $actualRTP = ($summed['win_amount'] / $summed['bet_amount']) / 100;
                $theoreticalRTP = $game['payout_percent'];
                if (($actualRTP > ($theoreticalRTP + ($percent_thold / 100)))) {
                    // Triggers AML34 - Daily
                    if (phive()->fDate(phive()->hisMod("-1 day")) == $day_key) {
                        $this->uh->logTrigger($uid, 'AML34', "Real RTP of $gamePlayed was $actualRTP");
                    }
                    $prof_day_cnt++;
                }
            }

            if ($prof_day_cnt > $count_thold) {
                // Trigger AML42 - Subsequent days
                // To retrigger the log ($days_thold) days must pass
                $date = $this->uh->getTriggerCreationDate($uid, 'AML42');
                if (empty($date) || strtotime($date) > strtotime($sstamp)) {
                    $this->uh->logTrigger($uid, 'AML42',
                        "Positive RTP return: $prof_day_cnt days out of $days_thold days ");
                }
            }
        }

        error_log("FR2". date('Y-m-d H:i:s'));


    }

    /**
     * AML41
     *  Should only trigger once every 30 days and change it so its based on % of wager.
     *  So only trigger if they received 2.5% bonus of total wager and the player has more than 2 active linked accounts via either device, IP, password or all.
     *
     * OLD AML41
     *  35% of gross deposit awarded in bonuses with 2 or more accounts using the same IP
     *
     * @param string $day
     */
    public function checkBonusAbuse($uid, $session)
    {
        $trigger = "AML41";
        $num_days = $this->getAndCacheConfig('AML', "$trigger-days", 30);
        if ($this->uh->hasTriggeredLastPeriod($uid, $trigger, $num_days) || empty($session['ip']) || empty($session['fingerprint'])) {
            return;
        }

        $percent = $this->getAndCacheConfig('AML', "$trigger-percent", 2.5) / 100;
        $sums = phive('SQL')->sh($uid)->loadAssoc("
                    SELECT SUM(bets) AS total_wagered, SUM(rewards) AS bonuses
                    FROM users_daily_stats
                    WHERE user_id = $uid");
        $u = cu($uid);
        $cur = $u->getCurrency();
        $today = phive()->today();
        $wager = chgToDefault($cur, $sums['total_wagered'], 1, $today);
        $bonus = chgToDefault($cur, $sums['bonuses'], 1, $today);
        if ($bonus < ($wager * $percent)) {
            return;
        }
        $password = $u->getAttribute('password');
        $link_limits = $this->getAndCacheConfig('AML', "$trigger-ipLinks", 2);
        $sessions = phive('SQL')->shs()->loadArray("
                        SELECT us.id, us.user_id
                        FROM users_sessions AS us
                        WHERE us.user_id != {$uid}
                          AND (fingerprint = '{$session['fingerprint']}' OR ip = '{$session['ip']}')
                        UNION
                        SELECT us.id, us.user_id
                        FROM users_sessions AS us
                          INNER JOIN users AS u ON u.id = us.user_id
                        WHERE us.user_id != {$uid}
                          AND u.password = '{$password}';"
        );

        if (count($sessions) < $link_limits) {
            return;
        }

        $users_list = array_unique(array_column($sessions, 'user_id'));
        $users_string = implode(', ', $users_list);
        $description = " 2.5% of total wager awarded in bonuses with 2 or more active links as: {$users_string}";
        $this->uh->logTrigger($uid, $trigger, $description, false, false, '', $users_string);

    }

    /**
     * This function is triggered on login and will perform a PEP/SL check every 4 months on the user.
     * We extract the most recent "xxx_pep_res" from users_settings to determine if 4 months have passed.
     *
     * Special note:
     * we skip this check during registration as it's not a recurrent check + it will fire twice (registration + login)
     *
     * @param DBUser $user
     * @return bool
     */
    public function pepRegularCheck($user)
    {

        if (!phive('Licensed')->getSetting('kyc_suppliers')['config']['recurrent_enabled']) {
            return;
        }

        $last_checked = phive('SQL')->sh($uid = $user->getId())->getValue("
            SELECT created_at
            FROM users_settings
            WHERE user_id = {$uid}
            AND (setting = 'id3global_pep_res' OR setting = 'acuris_pep_res')
            ORDER BY created_at DESC
        ");

        $registered_today = $user->getData()['register_date'] == phive()->today();
        if ($registered_today){
            return false;
        }
        $frequency = licOrFunc('pepRegularCheckFrequency', function() {
            return 30 * 4;
        },[], $user);
        if (!empty($last_checked) && phive()->subtractTimes(phive()->hisNow(), $last_checked, 'd') < $frequency) {
            return false;
        }

        // This will trigger AML flag inside "onKycCheck" if the check fail on all supplier.
        lic('checkKyc', [$user, true], $user);
    }

    public function pepYearlyONCheck()
    {
        $users = phive('SQL')->shs()->loadArray("
              SELECT u.id, MAX(us.created_at) AS lastCheckDate
                FROM users u
                RIGHT JOIN users_settings us_main_province
                    ON us_main_province.user_id = u.id
                    AND us_main_province.setting = 'main_province'
                    AND us_main_province.value = 'ON'
                LEFT JOIN users_settings us
                    ON us.user_id = u.id
                    AND (us.setting = 'id3global_pep_res' OR us.setting = 'acuris_pep_res')
                WHERE u.active = 0
                GROUP BY u.id
                HAVING lastCheckDate < DATE_SUB(NOW(), INTERVAL 1 YEAR);
                ");

        foreach ($users as $userId){
            $user = cu($userId['id']);
            $pepCheckNumber = $user->getSetting('pepCheckNumber');
            $pepCheckNumber = is_numeric($pepCheckNumber) ? $pepCheckNumber : 0;
            if ($pepCheckNumber < 5 && ($user->isBlocked() || $user->isSelfExcluded() || $user->isSuperBlocked())) {
                $lastCheckDate = $userId['lastCheckDate'];
                if (!empty($lastCheckDate) && (phive()->subtractTimes(phive()->hisNow(), $lastCheckDate, 'd') >= 365)) {
                    lic('checkKyc', [$user, true], $user);
                    $user->SetSetting('pepCheckNumber', $pepCheckNumber + 1);
                }
            }
        }
    }

    /**
     * Performs pep verification for users which had WorldPay transactions for last 30 days
     * and last PEP verification is done more than 30 days ago
     */
    public function pepWorldpayCheck()
    {
        $users = phive('Cashier')->getWorldpayUsersForPepVerification();

        foreach ($users as $userId) {
            $user = cu($userId);

            if (!$user->isBlocked() && !$user->isSelfExcluded() && !$user->isSuperBlocked()) {
                lic('checkKyc', [$user, true], $user);
            }
        }
    }

    /**
     * Age and PEP checks needs to be performed regardless gamstop fails or not.
     *
     * @param $user
     */
    public function onLoginWhenSelfExcluded($uid)
    {
        $user = cu($uid);
        $this->pepRegularCheck($user);
    }

    /**
     * Age and PEP checks needs to be performed regardless user has self-lock.
     *
     * @param $uid
     */
    public function onLoginWhenSelfLocked($uid)
    {
        $user = cu($uid);
        $this->pepRegularCheck($user);
    }

    /**
     * Add the user to third party, Monitoring if an user is PEP/SL
     *
     * @param $user
     * @param bool $edit
     */
    public function addUserToExternalKycMonitoring($user, $edit = false)
    {
        if (!phive('Licensed')->getSetting('kyc_suppliers')['config']['monitoring_enabled']) {
            return;
        }
        lic('handleUsersOnExternalKycMonitoring', [$user, $edit], $user);
    }

    /**
     * When an user updates the personal data, the system sends the new information to the third party to know if the player is PEP/SL
     *
     * @param $uid
     */
    public function updateUserOnExternalKycMonitoring($uid)
    {
        $user = cu($uid);
        $this->addUserToExternalKycMonitoring($user, true);
    }

}
