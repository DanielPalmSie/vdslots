<?php
require_once __DIR__ . '/../Cashier/Mts.php';

class TestUser extends TestPhive{

    function __construct(){
        $this->db  = phive('SQL');
        $this->uh  = phive('UserHandler');
        $this->mh  = phive('MailHandler2');
    }

    /*
       http://www.videoslots.loc/phive/modules/Micro/registration_new.php?step1=true&lang=en

       conditions	1
       country	SE
       country_prefix	46
       email	qwewrgw@wgwrwgre.se
       mobile	8798746546876712
       password	Asdf1234
       personal_number
       privacy	1


       --------------

       http://www.videoslots.loc/phive/modules/Micro/registration_new.php?lang=en

       address	ytruytfuytfjhgfjhf
       birthdate	09
       birthmonth	07
       birthyear	1960
       bonus_code
       city	trytrfyt
       currency	SEK
       dob	1960-07-09
       email_code	8023
       firstname	iutyfkugfkjhgf
       lastname	tfuytfjhgfjhgfjhgf
       newsletter	0
       opt_in_promotions	1
       over18	1
       preferred_lang	en
       sex	Male
       zipcode	45654
     */
    function testRegistrationStep1(){
        $_SESSION = [];
        $fields = [
            'conditions'      => 1,
            'country'         => 'SE',
            'country_prefix'  => '46',
            'email'           => uniqid().'@'.uniqid().'.se',
            'mobile'          => rand(1000000, 10000000),
            'password'        => 'Asdf1234'.uniqid(),
            'personal_number' => '',
            'privacy'         => 1
        ];
    ?>
    <form action="http://www.videoslots.loc/phive/modules/Micro/registration_new.php?step1=true&lang=en" method="post" target="_blank" rel="noopener noreferrer">
        <?php foreach($fields as $name => $value): ?>
            <?php echo $name ?>: <input name="<?php echo $name ?>" type="text" value="<?php echo $value ?>"><br>
        <?php endforeach ?>
        <input type="submit" name="submit" value="submit">
    </form>
    <?php
    }

    function testRegistrationStep2(){
        $u_obj = cu($_SESSION['rstep1']['user_id']);

        foreach(['firstname', 'lastname', 'sex', 'address'] as $erase){
            $u_obj->setAttr($erase, '');
        }


        $fields = [
            'address'           => uniqid(),
            'birthdate'         => '09',
            'birthmonth'        => '07',
            'birthyear'         => rand(1940, 1990),
            'bonus_code'        => '',
            'city'              => uniqid(),
            'currency'          => 'SEK',
            'firstname'         => uniqid(),
            'lastname'          => uniqid(),
            'newsletter'        => 1,
            'opt_in_promotions' => 1,
            'over18'            => 1,
            'preferred_lang'    => 'en',
            'sex'               => 'Male',
            'zipcode'           => rand(11111, 99999)
        ];

        $fields['dob'] = $fields['birthyear'].'-'.$fields['birthmonth'].'-'.$fields['birthdate'];
        $fields['email_code'] = $u_obj->getSetting('email_code');
        ?>
        <form action="http://www.videoslots.loc/phive/modules/Micro/registration_new.php?lang=en" method="post" target="_blank" rel="noopener noreferrer">
            <?php foreach($fields as $name => $value): ?>
                <?php echo $name ?>: <input name="<?php echo $name ?>" type="text" value="<?php echo $value ?>"><br>
            <?php endforeach ?>
            <input type="submit" name="submit" value="submit">
        </form>
        <?php
    }





    function testGamstop() {

        $check_latest = function () {
            return $this->db->loadAssoc("SELECT * FROM external_audit_log ORDER BY id DESC LIMIT 0,1");
        };

        $this->db->truncate('external_audit_log');

        $se_user = cu('devtestse');

        $se_res  = $this->uh->checkGamStop($se_user);

        if ($se_res != 'N' || !empty($check_latest())) {
            die('Not UK user failed');
        } else {
            echo "Non UK user: ok\n";
        }

        $devtestgb_res  = $this->uh->checkGamStop(cu('devtestgb'));

        if (!is_string($devtestgb_res) || empty($check_latest())) {
            die('UK user failed');
        } else {
            echo "UK user: ok\n";
        }

        //Load test data file from Gamstop

        $csv = new ParseCsv\Csv();
        $csv->parse('gamstop_test_data.csv');
        $i = 0;
        foreach($csv->data as $row){

            $row['country'] = 'GB';
            $res = $this->uh->checkGamStop($row);

            if($res != $row['res']) {
                echo "Test data result does not match for test id: {$row['t_id']}\n";
            }

            $i++;
        }

        echo "$i fake test users processed\n";


    }

    function testCurrencyMove($u, $cur){
        $move_tbls = [
            'trophy_events',
            'trophy_award_ownership',
            'tournament_entries',
            'users_settings',
            'bonus_entries',
            'race_entries',
            'lga_log',
            'users_sessions',
            'vouchers',
            'first_deposits',
            'users_game_sessions',
            'users_comments',
            'users_games_favs',
            'users_blocked',
            'user_flags',
            'triggers_log',
            'users_notifications'
        ];

        $uid = uid($u);

        $u->setAttr('nid', 123456789);

        $rg = rgLimits();
        foreach($rg->getMoneyLimits() as $rgl_type){
            $rg->addLimit($u, $rgl_type, 'week', 10000);
        }

        foreach(range(30, 31) as $qt_type){
            $this->db->sh($u)->insertArray('queued_transactions', ['amount' => 10000, 'user_id' => $uid, 'transactiontype' => $qt_type]);
        }

        $pr_cnt = function($u) use ($move_tbls){
            $uid = uid($u);
            foreach($move_tbls as $tbl){
                $cnt = $this->db->sh($u)->getValue("SELECT COUNT(*) FROM `$tbl` WHERE user_id = $uid");
                echo "$tbl: $cnt\n";
            }

            $card_cnt = phive('SQL')->doDb('mts')->getValue("SELECT COUNT(*) FROM `credit_cards` WHERE user_id = $uid");
            echo "mts.credit_cards: $card_cnt\n";

            $doc_cnt = phive('SQL')->doDb('dmapi')->getValue("SELECT COUNT(*) FROM `documents` WHERE user_id = $uid");
            echo "dmapi.documents: $doc_cnt\n";
        };

        echo "Before move:\n";
        $pr_cnt($u);

        $new_usr = $this->uh->moveUserToCurrency($u, $cur, '_oldtest');
        echo "Error msg: {$new_usr->data['username']}";

        echo "\nAfter move:\n";
        $pr_cnt($new_usr);

        echo "\nOld user after move:\n";
        print_r($u);

        echo "\nNew user after move:\n";
        print_r($new_usr);

        $new_id = uid($new_usr);

        foreach(['rg_limits', 'queued_transactions'] as $tbl){
            $rows = $this->db->sh($new_id)->loadArray("SELECT * FROM $tbl WHERE user_id = $new_id");
            echo "\n$tbl after move:\n";
            print_r($rows);
        }

        $u->refresh();
        echo "\nOld NID: {$u->data['nid']}, new NID: {$new_usr->data['nid']}\n";
    }


    function testBonusWrongGame($u, $bid = 1001){
        $this->db->truncate('bonus_entries');
        // 100 + the 10 payout from the bonus below
        $u->setAttr('cash_balance', 10000);
        phive('Bonuses')->addDepositBonus($u->getId(), $bid, 10000, true);
    }

    // 2489 is a VIP cash balance that is excluding table games
    function initBonusTesting($u, $bid = 1001, $keep_winnings = 1){
        $uid = uid($u);
        foreach(['bonus_entries', 'bets', 'wins', 'cash_transactions'] as $tbl)
            $this->db->delete($tbl, ['user_id' => $uid], $uid);
        $this->db->query("UPDATE bonus_types SET keep_winnings = $keep_winnings");

        // 100 + the 10 payout from the bonus below
        $u->setAttr('cash_balance', 10000);
        phive('Bonuses')->addDepositBonus($u->getId(), $bid, 10000, true);

    }

    function testKeepWinnings($u, $keep_winnings = 1){
        $this->initBonusTesting($u, 1001, $keep_winnings);
        sleep(2);
        // Simulate 10 in winnings
        $this->db->insertArray('bets', ['user_id' => $u->getId(), 'amount' => 1000]);
        $this->db->insertArray('wins', ['user_id' => $u->getId(), 'amount' => 2000]);
        $u->incAttr('cash_balance', 1000);

        // Simulated payout
        //$this->db->updateArray('bonus_entries', ['reward' => 9000], ['id' => 1]);
        $this->db->query("UPDATE bonus_entries SET reward = reward * 0.9");
        //$b_entries = phive('Bonuses')->getDepositBonusEntries($u->getId(), 'active');
        //print_r($b_entries);

        phive('Bonuses')->fail(1, 'Deposit bonus fail', $u->getId());

        $entries = $this->db->loadArray("SELECT * FROM cash_transactions");
        print_r($entries);


    }

    // Default IP is a known TOR exit node.
    function testAmlLogin($uid, $delete = true, $ip = '95.105.221.15'){
        $u   = cu($uid);
        $uid = uid($u);

        if($delete){
            foreach(['triggers_log', 'users_sessions'] as $tbl)
                $this->db->delete($tbl, ['user_id' => $uid], $uid);
        }

        $u->setSetting('sar-flag', 1);

        $uid = $u->getId();

        $ins = [
            'user_id' => $uid,
            'ip'      => $ip,
        ];

        $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', $ins);
        $ins['user_id'] = 123;
        $this->db->sh($ins, 'user_id', 'users_sessions')->insertArray('users_sessions', $ins);
        $u->setAttr('cur_ip', $ip);
        phive('Cashier/Aml')->onLogin($uid);
        $log = $this->db->sh($uid)->loadArray("SELECT * FROM triggers_log WHERE user_id = $uid");
        print_r($log);
        //$sessions = $this->db->sh($uid)->loadArray("SELECT * FROM users_sessions WHERE user_id = $uid") ;
        //print_r($sessions);
    }

    function testFraudCheck($ud){
        $u = cu($ud);
        $u->setAttr('active', 1);
        return $this->uh->lgaFraudCheck($ud);
    }

    /**
     * @param $random_db_user
     * @param array $test_data_provider
     * @param callable $attr_change_mock_function
     * @param callable $change_value_function
     * @param int $levenshtein_thold
     * @return array
     * @throws Exception
     */
    function testSimilarity($random_db_user, $test_data_provider=[], callable $attr_change_mock_function, callable $change_value_function, $levenshtein_thold = 30){
        $output_array = [];

        foreach ($test_data_provider as $key => $test_data_row) {
            $attrs_to_change = $test_data_row[0];
            $truth_test = $test_data_row[1];
            $mocked_user = $attr_change_mock_function($random_db_user, $attrs_to_change, $change_value_function);

            list($similar, $the_rest) = $this->uh->checkSimilar($mocked_user, $levenshtein_thold, [$random_db_user]);

            if ( (count($similar) > 0 && $truth_test) || (count($similar) == 0 && !$truth_test) ) {
                echo "Similar test passed for attributes " . implode(', ', $attrs_to_change) . ";\n";
            } else {
                throw new \Exception("Similar test FAILED for attributes ".implode(', ', $attrs_to_change).";");
            }
            $output_array['similar_row_'.$key] = $similar;
        }

        return $output_array;
    }

    function truncateMailTables(){
        $this->db->truncate('bonus_types', 'actions', 'user_flags', 'users_settings', 'vouchers', 'mailer_queue', 'mailer_log', 'trans_log');
    }

    function testRepeatEmails($sdate, $edate, $reset = false){
        // We truncate all involved tables that could interfere with sending emails or make it harder to inspect the DB afterwards.
        if($reset)
            $this->truncateMailTables();
        $days = phive()->getDateInterval($sdate, $edate);
        foreach($days as $day){
            $res                 = $this->mh->mailSchedule($day, true);
            $content             = "Day: $day, Res: $res\n";
            $_SESSION['sent_to'] = [];
            $mails               = $this->db->loadArray("SELECT * FROM mailer_queue LIMIT 10");
            $content            .= var_export($mails, true)."\n\n";
            echo $content;
            file_put_contents('result.txt', $content, FILE_APPEND);
            $this->db->truncate('mailer_queue');
        }
        // select * from vouchers group by voucher_code
        // select * from mailer_queue group by subject
    }

    function testWeek2Mail(){
        $sql = phive('SQL');
        $sql->shs()->query("delete from users_settings where setting = 'monthly-week2-num'");
        $sql->truncate('user_flags', 'vouchers', 'mailer_queue', 'mailer_log', 'trans_log');
        shell_exec("/var/www/videoslots/reimport_table.sh bonus_types");
        $sql->syncGlobalTable('bonus_types');
        phive('MailHandler2')->weekMailCommon('monthly-week2');
    }

    function checkEmailToMovedCurrency($mail_trigger = 'monthly-week2'){
        $moved = $this->db->shs()->loadArray("SELECT * FROM users_settings WHERE setting = 'mvcur_old_id'");
        foreach($moved as $s){
            $user = cu($s['user_id']);
            //$old = cu($s['value']);

            if(($user->isBlocked() || ($user->isBonusBlocked()) && !$test) || phive('MailHandler2')->isMailBlocked($user->data)){
                continue;
            }

            if(!phive('MailHandler2')->voucherMailGateKeeper($mail_trigger, $user)){
                continue;
            }

            if(!$user->hasSetting('privacy-main-promo-email') || $user->getSetting('privacy-main-promo-email') == 0){
                continue;
            }

            $new_sum = phive('Cashier')->getUserDepositSum($s['user_id']);
            if(empty($new_sum)){
                $old_sum = phive('Cashier')->getUserDepositSum($s['value']);
                if($old_sum > 10000){
                    $mails = $this->db->loadArray("SELECT * FROM mailer_queue WHERE to = '{$new->data['email']}'");
                    if(empty($mails)){
                        //print_r(['user' => $s, 'old_sum' => $old_sum]);
                        //exit;
                        echo "{$s['user_id']} did NOT get the email, BAD. Old id: {$s['value']}, old sum: $old_sum\n";
                        exit;
                    } else {
                        echo "{$s['value']} got the email, GOOD.\n";
                    }
                }
            }
        }
    }

    function testMtsGetCards($uid, $active = true){
        return Mts::getInstance('', $uid)->getCards(0, false, $active);
    }

    function testReloadBonus($uname, $rcode, $amount = 10000){
        $u = cu($uname);
        $this->db->delete('bonus_entries', ['user_id' => $u->getId()], $u->getId());
        phive('Bonuses')->setCurReload($rcode, $u);
        phive('Bonuses')->handleReloadDeposit($u, $amount, $amount, $u->getCurrency());
        $res = $this->db->sh($u, 'id', 'bonus_entries')->loadArray("SELECT * FROM bonus_entries WHERE user_id = {$u->getId()}");
        print_r($res);
    }

    // 11 welcome = 2122
    // Non wager = 5034
    function testFrbStart($uname, $bid = 2122){
        $u = cu($uname);
        $this->db->delete('bonus_entries', ['user_id' => $u->getId()], $u->getId());
        return phive('Bonuses')->addUserBonus($u->getId(), $bid, true);
    }

    //100week09
    function testEmailBonus($user, $bid){
        echo phive('Bonuses')->checkBonusEmail($user, $bid) ? 'can activate bonus' : 'can not activate bonus';
    }

    function testPartnerApi($date, $username){
        $date_param = ['date' => $date];
        $map = [
            'send_users_daily_stats' => $date_param,
            'check_signups'          => $date_param,
            'check_username'         => ['username' => $username],
            'check_first_deposits'   => $date_param,
            'check_deposits'         => $date_param,
            'check_logins'           => $date_param
        ];
        foreach($map as $action => $params)
            $this->testPartnerApiAction($action, $params);
    }

    function testPartnerApiAction($action, $params){
        $url  = phive()->getSiteUrl().'/phive/modules/Affiliater/json/rc.php';
        echo "Executing $action\n";
        $json = json_encode(['key' => phive( 'Affiliater' )->getSetting( 'rc_key' ), 'action' => $action, 'params' => $params]);
        $res  = phive()->post($url, $json);
        $res = substr($res, 0, 100);
        echo "Action: $action, Res: $res \n\n";
    }

    function makeBetWinTmp($date){
        $sdate 	= $date.' 00:00:00';
        $edate 	= $date.' 23:59:59';
        phive('Casino')->makeBetWinTmp($sdate, $edate);
    }

    function calcGameUserStats($date){
        $this->db->truncate('users_daily_game_stats', 'network_stats');
        if($this->db->isSharded('users_daily_game_stats')){
            $this->db->loopShardsSynced(function($db, $shard, $id) use($date){
                phive('MicroGames')->calcGameUserStats($date, $db);
            });
        }else
            phive('MicroGames')->calcGameUserStats($date);
        phive('UserHandler')->aggregateUserStatsTbl('users_daily_game_stats', $date);
        $master_cnt = $this->db->getValue("SELECT COUNT(*) FROM users_daily_game_stats");
        $shard_cnt = current($this->db->shs('sum')->loadArray("SELECT COUNT(*) FROM users_daily_game_stats")[0]);
        echo "\nMaster cnt: $master_cnt, shard cnt: $shard_cnt\n";
        phive('MicroGames')->calcNetworkStats($date);
    }

    function calcDailyStats($date){
        $sdate 	= $date.' 00:00:00';
        $edate 	= $date.' 23:59:59';
        $this->db->truncate('users_daily_stats');
        if($this->db->isSharded('users_daily_stats')){
            $this->db->loopShardsSynced(function($db, $shard, $id) use($sdate, $edate, $date){
                echo "\n$id\n";
                phive('Cashier')->calcUserCache($sdate, $edate, $db);
            });
            phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats', $date);
            $master_cnt = $this->db->getValue("SELECT COUNT(*) FROM users_daily_stats");
            $shard_cnt = current($this->db->shs('sum')->loadArray("SELECT COUNT(*) FROM users_daily_stats")[0]);
            echo "\nMaster cnt: $master_cnt, shard cnt: $shard_cnt\n";
        }else
            phive('Cashier')->calcUserCache($sdate, $edate);
    }

    function calcDailyMpStats($date = ''){
        $this->db->truncate('users_daily_stats_mp');
        if($this->db->isSharded('users_daily_stats_mp')){
            $this->db->loopShardsSynced(function($db, $shard, $id) use($date){
                phive('Tournament')->calcDailyStats($date, $db);
            });
            phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats_mp', $date);
            $master_cnt = $this->db->getValue("SELECT COUNT(*) FROM users_daily_stats_mp");
            $shard_cnt = current($this->db->shs('sum')->loadArray("SELECT COUNT(*) FROM users_daily_stats_mp")[0]);
            echo "\nMaster cnt: $master_cnt, shard cnt: $shard_cnt\n";
        }else
            phive('Tournament')->calcDailyStats($date);
    }

    function resetDmapi(){
        phive('SQL')->doDb('dmapi')->truncate('documents', 'files');
    }

    function setHasAllPSPs($u, $card_num = '3243 56** **** 1234'){
        $u->verify();
        $u->setSetting('majority_date', phive()->hisNow());
        $u->deleteSettings('source_of_funds_status', 'proof_of_source_of_funds_activated', 'proof_of_wealth_activated');

        // $this->resetDmapi();
        $uid = uid($u);

        $settings = [];
        foreach(Mts::getPsps() as $psp){
            $settings[$psp] = 'has_'.$psp;
        }

        $settings = array_merge($settings, phive('Cashier')->getDepositInfoMap($u));

        foreach($settings as $psp => $setting){
            echo "$psp\n";
            //if(in_array($psp, ['inpay'])){
            //    continue;
            //}
            phive('Dmapi')->createEmptyDocument($u->getId(), $psp);
            $u->setSetting($setting, 1);
            phive('Casino')->depositCash($uid, 10000, $psp, uniqid());
        }


        phive('UserHandler')->logAction($u, 'create empty documents for a test user', 'creating_documents');

        foreach(phive('Dmapi')->map as $psp => $pic){
            phive('Dmapi')->createEmptyDocument($u->getId(), $psp);
        }

        phive('Casino')->depositCash($uid, 10000, 'wirecard', uniqid(), 'visa', $card_num, 122);
        phive('Dmapi')->createEmptyDocument($u->getId(), 'bankpic');
        $mts_db = phive('SQL')->doDb('mts');
        $mts_db->truncate("credit_cards");

        $mts_db->insertArray('credit_cards', [
            'transaction_id' => 29,
            'customer_id' => 100,
            'user_id' => $u->getId(),
            'card_num' => $card_num,
            'exp_year' => 2022,
            'exp_month' => 12,
            'three_d' => 1,
            'active' => 1,
            'verified' => 1,
            'approved' => 1,
            'is_unique' => 1,
            'country' => $u->getCountry()
        ]);

        $mts_db->insertArray('credit_cards', [
            'transaction_id' => 29,
            'customer_id' => 100,
            'user_id' => $u->getId(),
            'card_num' => '4243 56** **** 1234',
            'exp_year' => 2022,
            'exp_month' => 12,
            'three_d' => 1,
            'active' => 1,
            'verified' => 1,
            'approved' => 1,
            'is_unique' => 1,
            'country' => $u->getCountry()
        ]);

        foreach([1, 2] as $cid){
            phive('Dmapi')->createEmptyDocument($u->getId(), 'adyen', 'visa', $cid, $cid);

            $mts_db->insertArray('transactions', [
                'type' => 0,
                'customer_id' => 100,
                'user_id' => $u->getId(),
                'card_id' => $cid,
                'amount' => 10000,
                'supplier' => 'adyen',
                'status' => 10
            ]);

            $mts_db->insertArray('recurring_transactions', [
                'customer_id' => 100,
                'user_id' => $u->getId(),
                'card_id' => $cid,
                'amount' => 10000,
                'supplier' => 'adyen',
                'ext_id' => uniqid()
            ]);
        }

        //$mts_db->query("UPDATE transactions SET card_id = 1 WHERE id = 29");

        $mts = new Mts(Supplier::WireCard, $u->getId());
        $cards = $mts->getCards(0, CardVerifyStatus::Verified, CardStatus::Active, Supplier::$main_ccs, true);

        phive('SQL')->doDb('dmapi')->query("UPDATE documents SET status = 2");
        phive('SQL')->doDb('dmapi')->query("UPDATE files SET status = 2");

        $u->setSetting('mb_email', $u->getAttr('email'));
        $u->setSetting('net_account', '453501020503');
        $u->setSetting('instadebit_user_id', $u->getId());
    }

    function depositCash($uname, $type = 'neteller', $amount = 1000, $first_time = true){
        $u    = cu($uname);
        $uid  = $u->getId();
        $tbls = ['deposits', 'cash_transactions'];
        if($first_time)
            $tbls[] = 'first_deposits';
        foreach($tbls as $tbl)
            phive('SQL')->delete($tbl, ['user_id' => $uid], $uid);
        $target_balance = $u->getBalance() + $amount;
        $descr          = uniqid();
        $res_balance    = phive('Casino')->depositCash($u, $amount, $type, $descr, 'test', 'test', $descr, false, 'approved', null, 0);
        $u->setSetting("has_{$type}", 1);
        $negation       = $target_balance != $res_balance ? 'not' : '';
        echo "Balance change on $uname was $negation successful, target balance: $target_balance, actual balance: $res_balance\n";
        foreach($tbls as $tbl){
            $res = $this->db->sh($uid, '', $tbl)->loadAssoc("SELECT * FROM $tbl WHERE user_id = $uid");
            if(empty($res))
                echo "Deposit related table $tbl was not inserted\n";
        }
    }


    function changeBalance($uname, $amount = 100){
        $u              = cu($uname);
        $target_balance = $u->getBalance() + $amount;
        $descr          = uniqid();
        $res_balance    = phive('Casino')->changeBalance($u, $amount, $descr, 13);
        $negation       = $target_balance != $res_balance ? 'not' : '';
        echo "Balance change on $uname was $negation successful, target balance: $target_balance, actual balance: $res_balance\n";
    }

    function logAction($target_uname, $actor_uname){
        $tag      = uniqid();
        $this->uh->logAction($target_uname, $tag, $tag, true, $actor_uname);
        $target   = ud($target_uname);
        $action   = $this->db->sh($target, 'id', 'actions')->loadAssoc("SELECT * FROM actions WHERE target = {$target['id']} ORDER BY id DESC LIMIT 0,1");
        $negation = $action['tag'] != $tag ? 'not' : '';
        echo "Action on $target_uname by $actor_uname was $negation successfully insterted\n";
    }

    function logIp($target_uname, $actor_uname){
        $tag      = uniqid();
        $this->uh->logIp($actor_uname, $target_uname, $tag, $tag);
        $target   = ud($target_uname);
        $ip_log   = $this->db->sh($target, 'id', 'ip_log')->loadAssoc("SELECT * FROM ip_log WHERE target = {$target['id']} ORDER BY id DESC LIMIT 0,1");
        $negation = $ip_log['tag'] != $tag ? 'not' : '';
        echo "Log IP on $target_uname by $actor_uname was $negation successfully insterted\n";
    }

    function testRestrict($u_obj, $deposit_amount = 50000, $withdraw_amount = 50000, $dmapi_status = 0){
        phive('SQL')->doDb('dmapi')->query("UPDATE documents SET status = $dmapi_status WHERE user_id = ".uid($u_obj));
        $this->clearTable($u_obj, 'cash_transactions');
        $u_obj->unVerify();
        $u_obj->unRestrict();
        phive('Cashier')->insertTransaction($u_obj, $deposit_amount, 3, 'test');
        phive('Cashier')->insertTransaction($u_obj, -$withdraw_amount, 8, 'test');
        $restriction_reason = $this->uh->doCheckRestrict($u_obj);

        if ($restriction_reason) {
            $u_obj->restrict($restriction_reason);
        }
        echo $u_obj->isRestricted() ? "Restricted\n" : "NOT Restricted\n";
    }


}
