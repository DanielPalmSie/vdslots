<?php

use Carbon\Carbon;
use Carbon\CarbonInterface;


class TestArf extends TestPhive
{
    function testRG29($country = 'MT')
    {
        $trigger = 'RG29';
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $today = phive()->today();
        phive('SQL')->sh($user_id)->delete('users_realtime_stats', ['user_id' => $user_id]);
        $percentage_threshold = (int)phive('SQL')->loadAssoc(
            "SELECT config_value FROM config WHERE config_tag = 'RG' and config_name='$trigger-percentage'"
        )['config_value'];

        // average bet per spin
        $lifetimeAverageData = [
            ['bets' => 5000, 'bets_count' => 5, 'sub_days' => 1],
            ['bets' => 5000, 'bets_count' => 5, 'sub_days' => 2],
        ];
        $transactions_count = count($lifetimeAverageData);
        foreach ($lifetimeAverageData as $lifetimeAverage) {
            phive('SQL')->sh($user_id)->insertArray(
                'users_daily_game_stats',
                [
                    'username' => $user->getUsername(),
                    'affe_id' => 0,
                    'firstname' => 'Ben',
                    'user_id' => $user_id,
                    'lastname' => 'Stiller',
                    'bets' => $lifetimeAverage['bets'],
                    'bets_count' => $lifetimeAverage['bets_count'],
                    'wins_count' => 0,
                    'date' => phive()->modDate($today, "-{$lifetimeAverage['sub_days']} days"),
                    'game_ref' => 'nyx251232',
                    'currency' => 'EUR',
                    'network' => 'nyx',
                    'country' => $country,
                ]
            );
        }

        // last day bet per spin.
        $how_many_times_bigger = ($percentage_threshold / 100);
        $coefficient_to_breach_the_threshold = 200; //cents
        $bets = (phive()->sum2d($lifetimeAverageData, 'bets') / $transactions_count) / $transactions_count *
            $how_many_times_bigger + $coefficient_to_breach_the_threshold;
        phive('SQL')->sh($user_id)->insertArray(
            'users_realtime_stats',
            [
                'date' => $today,
                'user_id' => $user_id,
                'currency' => 'EUR',
                'bets' => (int)$bets,
                'wins' => 0,
                'rewards' => 0,
                'jp_contrib' => 0,
                'bet_count' => $transactions_count,
            ]
        );
        sleep(5);
        phive('Cashier/Rg')->todayAgainstLifetimeAverageBetPerSpin($user_id, 60, $today);
        sleep(15);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}");
        $info = "User ID: {$user_id}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'deposits',
                'users_daily_game_stats',
                'users_realtime_stats',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "{$trigger} ERROR! {$info}";
            return false;
        }

        echo "{$trigger} OK -> " . json_encode($triggers) . chr(10);
        return true;
    }

    function testRG30($country = 'MT')
    {
        $trigger = 'RG30';
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $today = phive()->today();
        phive('SQL')->sh($user_id)->delete('users_realtime_stats', ['user_id' => $user_id]);
        $percentage_threshold = (int)phive('SQL')->loadAssoc(
            "SELECT config_value FROM config WHERE config_tag = 'RG' and config_name='$trigger-percentage'"
        )['config_value'];

        // average bet per spin
        $lifetimeAverageData = [
            ['bets' => 5000, 'bets_count' => 5, 'sub_days' => 1],
            ['bets' => 5000, 'bets_count' => 5, 'sub_days' => 2],
        ];
        $transactions_count = count($lifetimeAverageData);
        foreach ($lifetimeAverageData as $lifetimeAverage) {
            phive('SQL')->sh($user_id)->insertArray(
                'users_daily_game_stats',
                [
                    'username' => $user->getUsername(),
                    'affe_id' => 0,
                    'firstname' => 'Ben',
                    'user_id' => $user_id,
                    'lastname' => 'Stiller',
                    'bets' => $lifetimeAverage['bets'],
                    'bets_count' => $lifetimeAverage['bets_count'],
                    'wins_count' => 0,
                    'date' => phive()->modDate($today, "-{$lifetimeAverage['sub_days']} days"),
                    'game_ref' => 'nyx251232',
                    'currency' => 'EUR',
                    'network' => 'nyx',
                    'country' => $country,
                ]
            );
        }

        // last day bet per spin.
        $how_many_times_bigger = ($percentage_threshold / 100);
        $coefficient_to_breach_the_threshold = 5100; //cents
        $bets = (phive()->sum2d($lifetimeAverageData, 'bets') / $transactions_count) *
            $how_many_times_bigger + $coefficient_to_breach_the_threshold;
        phive('SQL')->sh($user_id)->insertArray(
            'users_realtime_stats',
            [
                'date' => $today,
                'user_id' => $user_id,
                'currency' => 'EUR',
                'bets' => (int)$bets,
                'wins' => 0,
                'rewards' => 0,
                'jp_contrib' => 0,
                'bet_count' => $transactions_count,
            ]
        );
        sleep(5);
        phive('Cashier/Rg')->todayAgainstLifetimeAverageDailyWager($user_id, 60, $today);
        sleep(15);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}");
        $info = "User ID: {$user_id}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'deposits',
                'users_daily_game_stats',
                'users_realtime_stats',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "{$trigger} ERROR! {$info}";
            return false;
        }

        echo "{$trigger} OK -> " . json_encode($triggers) . chr(10);
        return true;
    }

    function testRG38andRG39($country = 'MT')
    {
        $output = "";
        $triggers_threshold = phive('SQL')
            ->loadArray("SELECT name, ngr_threshold FROM triggers WHERE name IN ('RG38', 'RG39')");
        $game_session['end_time'] = phive()->today();
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        foreach ($triggers_threshold as $trigger) {
            $trigger_name = $trigger['name'];
            $ngr_threshold = $trigger['ngr_threshold'];
            $coefficient_to_breach_the_threshold = 100; //cents
            $wins = $frb_wins = $jp_contrib = $rewards = 25000;
            $bets = $ngr_threshold + ($wins + $frb_wins + $jp_contrib + $rewards) + $coefficient_to_breach_the_threshold;
            phive('SQL')->sh($user_id)->delete('users_realtime_stats', ['user_id' => $user_id]);
            phive('SQL')->sh($user_id)->delete('triggers_log', ['user_id' => $user_id, 'trigger_name' => $trigger_name]);
            phive('SQL')->sh($user_id)->insertArray(
                'users_realtime_stats',
                [
                    'date' => $game_session['end_time'],
                    'user_id' => $user_id,
                    'currency' => 'EUR',
                    'bets' => $bets,
                    'wins' => $wins,
                    'frb_wins' => $frb_wins,
                    'jp_contrib' => $jp_contrib,
                    'rewards' => $rewards,
                    'fails' => 0,
                ]
            );
            sleep(5);
            phive('Cashier/Rg')->checkLossAmountBasedOnNGR($user, $game_session);
            sleep(15);
            $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '{$trigger_name}' AND user_id = {$user_id}");
            $info = "User ID: {$user_id}" . chr(10);

            if (empty($triggers)) {
                $output .= "{$trigger_name} ERROR! {$info}\n";
            }

            $output .= "{$trigger_name} OK -> " . json_encode($triggers) . chr(10);
        }

        echo $output;

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'deposits',
                'users_realtime_stats',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );
        return true;
    }

    function testAML1()
    {
        $trigger = 'AML1';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];
        $this->deleteTrigger($trigger, $uid);
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid]);
        $cf = phive('Config')->getByTagValues('AML');
        $thold = $cf['AML1'];
        $ext_id = uniqid();
        phive('Casino')->depositCash($u->getUsername(), $thold - 1, 'wirecard', $ext_id, 'visa');
        if (empty(Phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid"))) {
            echo "No trigger deposit < $thold-> ok " . chr(10);
        } else {
            echo "ERROR!!!!";
        }
        sleep(5);
        $ext_id = uniqid();
        phive('Casino')->depositCash($u->getUsername(), $thold - 1, 'wirecard', $ext_id, 'visa');
        sleep(2);
        $trigger_deposit = Phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        if (!empty($trigger_deposit)) {
            echo " trigger deposit > $thold-> ok  " . chr(10);
            echo " trigger -> {$trigger_deposit['id']} " . chr(10);
        } else {
            echo "ERROR!!!!";
        }
        sleep(5);
        echo chr(10);
        phive('SQL')->sh($uid)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('pending_withdrawals', ['user_id' => $uid]);
        phive('CasinoCashier')->insertPendingNeteller($u, $thold - 1, '14789589', $thold - 1);
        sleep(2);
        if (empty(phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid"))) {
            echo "No trigger withdrawal < $thold-> ok " . chr(10);
        } else {
            echo "ERROR!!!!";
        }
        Phive('CasinoCashier')->insertPendingNeteller($u, $thold - 1, '14789589', $thold - 1);
        sleep(2);
        $trigger_withdrawal = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        if (!empty($trigger_withdrawal)) {
            echo " trigger withdrawal > $thold-> ok  " . chr(10);
            echo " trigger -> {$trigger_withdrawal['id']} " . chr(10);
        } else {
            echo "ERROR!!!!";
        }


    }

    // TODO @Paolo - check this
    //TEST 'RG32', 'RG33', 'RG34'
    function testChangeSessionTime()
    {
        $user = cu(5668669);
        $user = $user->data;
        $uid = $user['id'];
        $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND,created_at,ended_at)) as time_session  , ended_at  FROM users_sessions WHERE user_id =$uid  and ended_at !='0000-00-00 00:00:00' GROUP BY date(ended_at) ORDER BY  id DESC;";
        $times_sessions = phive('SQL')->sh($uid)->loadArray($sql);
        $avg_time = phive()->sum2d($times_sessions, 'time_session') / count($times_sessions);
        $avg_time = round($avg_time * 1.11);
        $sql = "SELECT SUM(TIMESTAMPDIFF(SECOND,start_time,end_time)) AS time_session , COUNT(id) AS n_times ,SUM(bet_amount) as amount  ,date(end_time) AS day  FROM users_game_sessions WHERE user_id = $uid  AND bet_cnt > 0 GROUP BY date(end_time)  ORDER BY  id DESC;";
        $games_sessions = phive('SQL')->sh($uid)->loadArray($sql);
        $game_amount = phive()->sum2d($games_sessions, 'amount') / phive()->sum2d($games_sessions, 'n_times');
        $game_amount = round($game_amount) * 1.11;
        phive('SQL')->delete('triggers_log', ["user_id" => $uid], $uid);;
        $triggers = ['RG32', 'RG33', 'RG34'];
        foreach ($triggers as $trigger) {
            phive('SQL')->query("DELETE FROM config  WHERE config_tag = 'RG' and  config_name='$trigger-percentage'");
            phive('SQL')->query("DELETE FROM config  WHERE config_tag = 'RG' and  config_name='$trigger-score-end'");
            phive('SQL')->query("DELETE FROM config  WHERE config_tag = 'RG' and  config_name='$trigger-score-start'");

            phive('Config')->getValue('RG', "$trigger-score-start", 0);
            phive('Config')->getValue('RG', "$trigger-score-end", 10);
            phive('Config')->getValue('RG', "$trigger-percentage", 10);
        }
        $end_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s', (strtotime($end_time) - $avg_time));
        $session = [
            'user_id' => $uid,
            'created_at' => $start_time,
            'updated_at' => $end_time,
            'ended_at' => $end_time,
            'equipment' => 'PC',
            'end_reason' => 'forced logout',
            'ip' => '',
            'fingerprint' => '',
            'ip_classification_code' => ''

        ];
        $session = phive('SQL')->sh($uid)->insertArray('users_sessions', $session);
        $end_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s', (strtotime($end_time) - 1));
        $ins = [

            'user_id' => $uid,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'bet_amount' => $game_amount * 0.88,
            'game_ref' => 'nyx240016',
            'ip' => '129.168.130.5',
            'device_type_num' => 0,
            'bet_cnt' => 1,
            'session_id' => $session,
            'win_amount' => 0,
            'win_cnt' => 0
        ];
        phive('SQL')->sh($uid)->insertArray('users_game_sessions', $ins);
        $end_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s', (strtotime($end_time) - $avg_time));
        $ins = [

            'user_id' => $uid,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'bet_amount' => $game_amount,
            'game_ref' => 'nyx240016',
            'ip' => '129.168.130.5',
            'device_type_num' => 0,
            'bet_cnt' => 1,
            'session_id' => $session,
            'win_amount' => 0,
            'win_cnt' => 0
        ];
        phive('SQL')->sh($uid)->insertArray('users_game_sessions', $ins);
        sleep(5);
        $today = phive()->today();
        phive('Cashier/Rg')->changeSessionTime($uid, 10, $today);
        sleep(1);
        phive('Cashier/Rg')->onSessionEnd($uid);
        foreach ($triggers as $trigger) {
            $sql = "SELECT * FROM triggers_log WHERE  user_id = $uid AND trigger_name = '$trigger'  AND date(created_at) ='$today' ";
            $trigger_sql = phive('SQL')->sh($uid)->loadAssoc($sql);
            if (!empty($trigger_sql)) {
                echo "$trigger OK " . chr(10);
            } else {
                echo " $trigger ERROR  !!!!!" . chr(10);
            }
        }
    }

    /**
     * Overview: Flag (RG35) should trigger if average bet on current game session has increased from previous game session with X% (200%)
     * Actioned: On end of game session
     *
     * @return void
     */
    function testRG35()
    {
        $trigger = 'RG35';
        $user = $this->getTestPlayer();
        $uid = $user->getId();
        // Manipulate user's GRS by changing user's age
        $interval = DateInterval::createFromDateString('16 years');
        $dob = (new DateTime('now'))->sub($interval);
        $user->updateData(['dob' => $dob->format('Y-m-d')]);
        $body = ['user_id' => $uid, 'jurisdiction' => $user->getJurisdiction()];
        phive()->postToBoApi("/risk-profile-rating/calculate-score/all", $body);
        sleep(3);

        $end_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s', (strtotime($end_time) - 600));
        $session = [
            'user_id' => $uid,
            'created_at' => $start_time,
            'updated_at' => $end_time,
            'ended_at' => $end_time,
            'equipment' => 'PC',
            'end_reason' => 'forced logout',
            'ip' => '',
            'fingerprint' => '',
            'ip_classification_code' => '',

        ];
        $session = phive('SQL')->sh($uid)->insertArray('users_sessions', $session);
        $ins = [
            [
                'user_id' => $uid,
                'start_time' => date('Y-m-d H:i:s', (strtotime($start_time) + 60)),
                'end_time' => date('Y-m-d H:i:s', (strtotime($end_time) - 240)),
                'bet_amount' => 10000,
                'game_ref' => 'nyx240016',
                'ip' => '129.168.130.5',
                'device_type_num' => 0,
                'bet_cnt' => 10,
                'session_id' => $session,
                'win_amount' => 0,
                'win_cnt' => 0,
            ],
            [
                'user_id' => $uid,
                'start_time' => date('Y-m-d H:i:s', (strtotime($start_time) + 120)),
                'end_time' => date('Y-m-d H:i:s', (strtotime($end_time) - 180)),
                'bet_amount' => 30100,
                'game_ref' => 'nyx240016',
                'ip' => '129.168.130.5',
                'device_type_num' => 0,
                'bet_cnt' => 10,
                'session_id' => $session,
                'win_amount' => 0,
                'win_cnt' => 0,
            ],
        ];
        phive('SQL')->sh($uid)->insert2DArr('users_game_sessions', $ins);
        sleep(3);
        $res = phive('UserHandler')->getGameSessions($uid, $ins[1]['start_time'], $ins[1]['end_time']);
        phive('Cashier/Rg')->onGameSessionEnd($uid, $res[0]['id']);
        sleep(15);
        $today = phive()->today();
        $sql = "SELECT * FROM triggers_log WHERE user_id = $uid AND trigger_name = '$trigger' AND date(created_at) ='$today' ";
        $trigger_sql = phive('SQL')->sh($uid)->loadAssoc($sql);

        $this->cleanupTestPlayer(
            $uid,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'users_sessions',
                'users_game_sessions',
                'risk_profile_rating_log',
            ]
        );

        $result = !empty($trigger_sql) ? "OK" : "ERROR!";
        echo "{$trigger} {$result} User ID: {$uid}" . chr(10);
    }

    function testAML17()
    {
        $trigger = 'AML17';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];
        $this->deleteTrigger($trigger, $uid);
        phive('SQL')->sh($uid)->delete('users_daily_stats', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid]);
        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 900000001,
            'deposits' => 9000001,
            'gross' => 10000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d')
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        $ext_id = uniqid();
        phive('Casino')->depositCash('devtestnl', 136014, 'Neosurf', $ext_id, 'Neosurf');
        sleep(3);
        $triggers = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        if (!empty($triggers)) {
            echo "{$trigger} trigger ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "{$trigger} ERROR!!!!" . chr(10);
        }
    }


    function testAML19()
    {
        $trigger = 'AML19';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];

        $this->deleteTrigger($trigger, $uid);
        phive('SQL')->sh($uid)->delete('users_daily_stats', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('users_game_sessions', ['user_id' => $uid]);

        $u->setSetting('pep_failure', 1);
        $u->setSetting('sanction_list_failure', 1);
        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 70000000,
            'deposits' => 9999900,
            'gross' => 5000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d')
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 1,
            'deposits' => 9000001,
            'gross' => 5000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d')
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        $ext_id = uniqid();
        phive('Casino')->depositCash('devtestnl', 136014, 'Neosurf', $ext_id, 'Neosurf');
        sleep(3);
        $triggers = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        $u->setSetting('pep_failure', 0);
        $u->setSetting('sanction_list_failure', 0);

        if (!empty($triggers)) {
            echo "{$trigger} trigger  ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "{$trigger} ERROR!!!!" . chr(10);
        }
    }


    function testAML20()
    {
        $trigger = 'AML20';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];
        $this->deleteTrigger($trigger, $uid);
        $u = (cu('devtestnl'));
        $card_country = 'ID';
        phive('Cashier/Fr')->checkCardCountry($u, $card_country);
        sleep(1);
        $first_trigger = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$uid ORDER BY  id  DESC");
        sleep(1);
        phive('Cashier/Fr')->checkCardCountry($u, $card_country);
        $no_trigger = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$uid ORDER BY  id  DESC");
        sleep(1);
        if ($first_trigger['created_at'] == $no_trigger['created_at'] && $first_trigger['id'] == $no_trigger['id']) {
            echo "OK ,just one trigger per card, trigger ->id  {$first_trigger['id']} " . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!!!!!";
        }


    }


    function testAML23()
    {
        $trigger = 'AML23';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];
        $this->deleteTrigger($trigger, $uid);
        phive('SQL')->sh($uid)->delete('users_daily_stats', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('users_game_sessions', ['user_id' => $uid]);
        $user_data = [
            'country' => $u->getCountry(),
            'dob' => $u->getData('dob'),
        ];
        $data = [
            'country' => 'AF',
            'dob' => '2010-05-07',
        ];
        phive('SQL')->sh($uid, '', 'users')->updateArray('users', $data, "id=$uid");

        if (phive('SQL')->isSharded('users')) {
            phive('SQL')->updateArray('users', $data, "id=$uid");
        }

        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 90000000,
            'deposits' => 9000001,
            'gross' => 5000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d'),
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 1,
            'deposits' => 9000001,
            'gross' => 5000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d'),
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        $ext_id = uniqid();
        phive('Casino')->depositCash('devtestnl', 136014, 'Neosurf', $ext_id, 'Neosurf');
        sleep(3);
        $triggers = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        phive('SQL')->sh($uid, '', 'users')->updateArray('users', $user_data, "id=$uid");

        if (phive('SQL')->isSharded('users')) {
            phive('SQL')->updateArray('users', $user_data, "id=$uid");
        }

        if (!empty($triggers)) {
            echo "{$trigger} trigger  ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "{$trigger} ERROR!!!!" . chr(10);
        }
    }


    function testAML28()
    {
        $trigger = 'AML28';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];
        $this->deleteTrigger($trigger, $uid);
        phive('SQL')->sh($uid)->delete('users_daily_stats', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('users_game_sessions', ['user_id' => $uid]);
        $user_data = [
            'country' => $u->getCountry(),
            'dob' => $u->getData('dob'),
        ];
        $data = [
            'country' => 'AF',
            'dob' => '2010-05-07',
        ];
        phive('SQL')->sh($uid, '', 'users')->updateArray('users', $data, "id=$uid");

        if (phive('SQL')->isSharded('users')) {
            phive('SQL')->updateArray('users', $data, "id=$uid");
        }
        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'gross' => 70000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d')
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        $ext_id = uniqid();
        phive('Casino')->depositCash('devtestnl', 136014, 'Neosurf', $ext_id, 'Neosurf');
        sleep(3);
        $triggers = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        phive('SQL')->sh($uid, '', 'users')->updateArray('users', $user_data, "id=$uid");

        if (phive('SQL')->isSharded('users')) {
            phive('SQL')->updateArray('users', $user_data, "id=$uid");
        }

        if (!empty($triggers)) {
            echo "{$trigger} trigger  ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "{{$trigger}} ERROR!!!!" . chr(10);
        }

    }

    function testAML33()
    {
        $uid = 5792789;
        $trigger = 'AML33';
        $this->deleteTrigger($trigger, $uid);
        phive('Cashier/Aml')->bankReceiverCheck($uid, 'asdasd', 'dasdsa');
        sleep(10);
        phive('Cashier/Aml')->bankReceiverCheck($uid, 'asdasd', 'dasdsa');
        if (!empty(Phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid"))) {
            echo 'OK' . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!";
        }
        phive('SQL')->sh($uid)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $uid]);
        // it was triggered , now shouldn't be triggered
        $receiver = "LEILA HANNELE KATANASHO-KORHONEN";
        $provider = 'trully';
        $uid = 5792789;
        Phive('Cashier/Aml')->bankReceiverCheck($uid, $receiver, $provider);
        sleep(10);
        Phive('Cashier/Aml')->bankReceiverCheck($uid, $receiver, $provider);
        if (empty(Phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid"))) {
            echo 'OK' . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!";
        }

    }


    function testAML35()
    {
        $trigger = "AML35";
        $user_1 = cu('devtestnl');
        $uid1 = $user_1->getId();
        $fingerprint = uniqid();
        phive('SQL')->sh($uid1)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $uid1]);
        phive('SQL')->sh($uid1)->delete('users_sessions', ['user_id' => $uid1]);
        $session_1 = [
            'created_at' => '2017-04-01 08:41:40',
            'updated_at' => '2017-04-02 07:53:14',
            'ended_at' => '2017-04-02 07:53:14',
            'equipment' => 'PC',
            'end_reason' => 'logout',
            'user_id' => $uid1,
            'ip' => '',
            'fingerprint' => '',
            'ip_classification_code' => ''
        ];
        phive('SQL')->sh($uid1)->insertArray('users_sessions', $session_1);
        sleep(3);
        phive('Cashier/Fr')->onLogin($uid1);
        sleep(2);
        $no_trigger = phive('SQL')->sh($uid1)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$uid1 ORDER BY  id  DESC");
        // empty ip and figerprint , not trigger
        if (empty($no_trigger)) {
            echo 'OK, no trigger: empty ip and empty fingerprint will not triggered ' . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!!";
        }
        $session_2 = [
            'created_at' => '2018-04-01 08:41:40',
            'updated_at' => '2018-04-02 07:53:14',
            'ended_at' => '2018-04-02 07:53:14',
            'equipment' => 'PC',
            'end_reason' => 'logout',
            'user_id' => $uid1,
            'ip' => '77.119.129.194',
            'fingerprint' => $fingerprint,
            'ip_classification_code' => ''
        ];
        phive('SQL')->sh($uid1)->insertArray('users_sessions', $session_2);
        sleep(5);
        phive('Cashier/Fr')->onLogin($uid1);
        sleep(3);
        $trigger1 = phive('SQL')->sh($uid1)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$uid1 ORDER BY  id  DESC");
        if (!empty($trigger1)) {
            echo "OK, trigger_id --> {$trigger1['id']}" . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!!";
        }

    }

    function testAML37()
    {
        $trigger = 'AML37';
        $user = $this->getTestPlayer('MT');
        $user_id = $user->getId();

        $grs_log = [
            'user_id' => $user_id,
            'rating_type' => 'AML',
            'rating' => 30,
            'created_at' => phive()->hisNow(),
            'rating_tag' => 'Social Gambler',
        ];
        phive('SQL')->sh($user_id)->delete('risk_profile_rating_log', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('first_deposits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('users_daily_stats', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->save('risk_profile_rating_log', $grs_log);

        $past_deposits = [
            ['amount' => 500000, 'sub_days' => 30],
            ['amount' => 500000, 'sub_days' => 20],
            ['amount' => 500000, 'sub_days' => 10],
        ];
        $current_config = phive('Config')->getValue('AML', "$trigger-thold-eur-cents", 1000000);
        echo "User id: {$user_id} current config value for $trigger-thold-eur-cents: $current_config \n";
        //Insert deposits done on the past 30 days
        foreach ($past_deposits as $deposit) {
            $fx_insert = [
                'multiplier' => $user->getAttr('currency') === 'EUR' ? 1 : 0.902,
                'code' => $user->getAttr('currency'),
                'day_date' => phive()->hisMod("- {$deposit['sub_days']} day"),
            ];
            phive('SQL')->sh($user_id)->save('fx_rates', $fx_insert);
            $dep_insert = [
                'user_id' => $user_id,
                'amount' => $deposit['amount'],
                'dep_type' => 'trustly',
                'timestamp' => phive()->hisMod("- {$deposit['sub_days']} day"),
                'ext_id' => uniqid(),
                'scheme' => "",
                'card_hash' => "",
                'loc_id' => "",
                'status' => "approved",
                'currency' => $user->getAttr('currency'),
                'display_name' => ucfirst(ucfirst('trustly')),
                'ip_num' => $user->getAttr('cur_ip'),
                'mts_id' => 0,
            ];
            $did = phive('SQL')->sh($user_id, '', 'deposits')->insertArray('deposits', $dep_insert);
            $uds_insert = [
                'user_id' => $user_id,
                'country' => $user->getAttr('country'),
                'username' => $user->getAttr('username'),
                'currency' => $user->getAttr('currency'),
                'bets' => ($deposit['amount'] * 1.5),
                'deposits' => $deposit['amount'],
                'gross' => 0,
                'affe_id' => '0',
                'firstname' => $user->getAttr('firstname'),
                'lastname' => $user->getAttr('lastname'),
                'aff_rate' => 0,
                'date' => phive()->hisMod("- {$deposit['sub_days']} day")
            ];
            $new_id = phive('SQL')->sh($user_id)->save('users_daily_stats', $uds_insert);
            $urs_insert = [
                'user_id' => $user_id,
                'currency' => $user->getAttr('currency'),
                'bets' => ($deposit['amount'] * 1.5),
                'deposits' => $deposit['amount'],
                'date' => phive()->hisMod("- {$deposit['sub_days']} day")
            ];
            $new_id = phive('SQL')->sh($user_id)->save('users_realtime_stats', $urs_insert);
        }
        $fx_insert = [
            'multiplier' => $user->getAttr('currency') === 'EUR' ? 1 : 0.902,
            'code' => $user->getAttr('currency'),
            'day_date' => phive()->hisNow(),
        ];
        phive('SQL')->sh($user_id)->save('fx_rates', $fx_insert);
        //Insert deposit on the day of run in order to trigger onDeposit sub-function
        phive('Casino')->depositCash($user_id, 1000, 'trustly', uniqid());
        sleep(120);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name ='$trigger' AND user_id = $user_id");
        $sql_settings = "SELECT
                            u.id,
                            us.value as deposit_block,
                            us2.value as play_block,
                            us3.value as withdrawal_block
                        FROM
                            users u
                        INNER JOIN users_settings us on
                            u.id = us.user_id
                            AND us.setting = 'deposit_block'
                        INNER JOIN users_settings us2 on
                            u.id = us2.user_id
                            AND us2.setting = 'play_block'
                        INNER JOIN users_settings us3 on
                            u.id = us3.user_id
                            AND us3.setting = 'withdrawal_block'
                        WHERE
                            u.id = $user_id";
        $user_settings = phive('SQL')->sh($user_id)->loadAssoc($sql_settings);
        $info = "User ID: {$user_id}" . chr(10);
        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "$trigger ERROR! $info";
        }

        echo "{$trigger} trigger OK: " . json_encode($triggers) . "\n users blocks \n" . json_encode($user_settings) . chr(10);
    }

    function testAML41(string $country = 'NL')
    {
        $test_users_ids = [];
        $trigger = "AML41";
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $test_users_ids[] = $user_id;
        $password = $user->getAttribute('password');
        $date = date('Y-m-d H:i:s');

        foreach ([uniqid(), uniqid()] as $key) {
            $email = "test" . $key . "@gmail.com";
            $userInsert = [
                'email' => $email,
                'mobile' => '49145454657851265',
                'country' => 'NL',
                'sex' => 'Male',
                'lastname' => 'Hammerbush',
                'firstname' => 'Frederic',
                'address' => 'Bahamas',
                'city' => 'Nuevo Sol',
                'zipcode' => '91101',
                'dob' => '1982-04-01',
                'preferred_lang' => 'en',
                'username' => $email,
                'password' => $password,
                'bonus_code' => ' ',
                'register_date' => '2019-06-17',
                'reg_ip' => '127.0.0.1.',
                'verified_phone' => 1,
                'friend' => ' ',
                'alias' => ' ',
                'cur_ip' => '127.0.0.1.',
                'currency' => 'EUR',
                'nid' => '123456',
            ];
            $new_user_id = phive('SQL')->sh(1)->insertArray('users', $userInsert);
            $test_users_ids[] = $new_user_id;
            $session_data = [
                'user_id' => $new_user_id,
                'created_at' => $date,
                'updated_at' => $date,
                'ended_at' => $date,
                'equipment' => 'PC',
                'end_reason' => 'forced logout',
                'ip' => '10.45.45.45',
                'fingerprint' => 'testfingerprint',
                'ip_classification_code' => ''
            ];
            phive('SQL')->sh($user_id)->insertArray('users_sessions', $session_data);
        }

        $session_data = [
            'user_id' => $user_id,
            'created_at' => $date,
            'updated_at' => $date,
            'ended_at' => $date,
            'equipment' => 'PC',
            'end_reason' => 'forced logout',
            'ip' => '10.45.45.45',
            'fingerprint' => 'testfingerprint',
            'ip_classification_code' => ''
        ];
        $session_id = phive('SQL')->sh($user_id)->insertArray('users_sessions', $session_data);
        $session = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM users_sessions WHERE id = $session_id");
        $percent_thold = phive('Config')->getValue('AML', "$trigger-percent", 2.5);
        $uds = [
            'user_id' => $user_id,
            'country' => strtolower($country),
            'username' => $user->data['username'],
            'currency' => $user->data['currency'],
            'bets' => 10000, //cents
            'rewards' => ($percent_thold + 0.5) * 100, // cents
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d')
        ];
        phive('SQL')->sh($user_id)->save('users_daily_stats', $uds);
        sleep(5);
        phive('Cashier/Fr')->invoke('checkBonusAbuse', $user_id, $session);
        sleep(10);
        $first_trigger = phive('SQL')
            ->sh($user_id)
            ->loadAssoc(
                "SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$user_id ORDER BY  id  DESC"
            );

        foreach ($test_users_ids as $user_id) {
            $this->cleanupTestPlayer(
                $user_id,
                [
                    'sport_transactions',
                    'triggers_log',
                    'deposits',
                    'users_sessions',
                    'users_game_sessions',
                    'users_daily_stats',
                    'actions' => 'target',
                    'users_settings',
                ]
            );
        }

        if (!empty($first_trigger)) {
            echo "OK, trigger id {$first_trigger['id']} user_id {$user_id}" . chr(10);
        } else {
            echo "ERROR!!!!!!" . chr(10);
        }
    }

    /**
     * Consecutive RTP: Positive return exceeding 5% on RTP in a number of subsequent sessions within 7 days
     *
     * Test flow:
     * - create 4 game sessions on four different days, to met the condition prof_day_cnt > AML42-consecutive-days
     * - each session must have RTP > theoretical RTP
     * - session RTP: win_amount / bet_amount / 100
     * - theoretical RTP: game RTP (let say 0.963) + (AML42-percent / 100 / 100)
     *
     * If the session RTP > theoretical RTP, 4 days in a row we should trigger this flag
     *
     * @param string $country
     * @param bool   $successful - set false if you want to fail this test
     *                             `true` - session RTP > theoretical RTP
     *                             `false` - session RTP = theoretical RTP
     *
     * @return void
     */
    function testAML42(string $country = 'GB', bool $successful = true)
    {
        $trigger = 'AML42';
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $start_time = Carbon::now()->subHour();
        $end_time = Carbon::now();
        $session = [
            'user_id' => $user_id,
            'created_at' => $start_time,
            'updated_at' => $end_time,
            'ended_at' => $end_time,
            'equipment' => 'PC',
            'end_reason' => 'forced logout',
            'ip' => '',
            'fingerprint' => '',
            'ip_classification_code' => ''
        ];
        $session = phive('SQL')->sh($user_id)->insertArray('users_sessions', $session);

        /**
         * @var array<int, Carbon> $consecutive_days
         */
        $consecutive_days = [
            Carbon::now()->subDays(5),
            Carbon::now()->subDays(4),
            Carbon::now()->subDays(3),
            Carbon::now()->subDays(2),
        ];

        $percent_thold = phive('Config')->getValue('AML', 'AML42-percent', 5) / 100;
        $gamePlayed = "nyx240016";
        $game = phive('MicroGames')->getByGameRef($gamePlayed);
        $coefficient_for_test_purpose = $successful ? 0.010 : 0;
        $rpt = $game['payout_percent'] + ($percent_thold/100) + $coefficient_for_test_purpose;
        $bet_amount = 200; // cents
        $win_amount = $bet_amount * ($rpt * 100);

        foreach ($consecutive_days as $date) {
            $end_time = $date->toDateTimeString();
            $start_time = $date->subMinute()->toDateTimeString();
            $ins = [
                'user_id' => $user_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'bet_amount' => $bet_amount,
                'game_ref' => $gamePlayed,
                'ip' => '129.168.130.5',
                'device_type_num' => 0,
                'bet_cnt' => 1,
                'session_id' => $session,
                'win_amount' => $win_amount,
                'win_cnt' => 1
            ];
            phive('SQL')->sh($user_id)->insertArray('users_game_sessions', $ins);
        }
        sleep(5);
        phive('Cashier/Fr')->everydayCron();
        sleep(10);
        $first_trigger = phive('SQL')
            ->sh($user_id)
            ->loadAssoc(
                "SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$user_id ORDER BY  id  DESC"
            );
        $this->cleanupTestPlayer(
            $user_id,
            [
                'sport_transactions',
                'triggers_log',
                'deposits',
                'users_sessions',
                'users_game_sessions',
                'actions' => 'target',
                'users_settings',
            ]
        );

        if (! empty($first_trigger)) {
            echo "OK, trigger id {$first_trigger['id']} " . chr(10);
        } else {
            echo "ERROR!!!!!!" . chr(10);
        }
    }

    function testAML46()
    {
        $trigger = 'AML46';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];
        $this->deleteTrigger($trigger, $uid);
        $ext_id = uniqid();
        phive('Casino')->depositCash('devtestnl', 136014, 'Neosurf', $ext_id, 'Neosurf');
        sleep(4);
        $first_trigger = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$uid ORDER BY  id  DESC");

        if (!empty($first_trigger)) {
            echo "OK, trigger id {$first_trigger['id']} " . chr(10);
        } else {
            echo "ERROR!!!!!!" . chr(10);
        }
    }

    function testAML48()
    {
        $uid = 5277932;
        $user = cu(5277932);
        $instadebit_id = 'gfdgdfgdg';
        $trigger = 'AML48';
        phive('Cashier/Fr')->instadebitFullnameMismatch($user, $instadebit_id);
        sleep(2);
        //Not match  , trigger
        if (!empty(Phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid"))) {
            echo 'OK' . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!";
        }
        phive('SQL')->sh($uid)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $uid]);
        //issue in live
        $instadebit_id = 'CHRISTOP-GOUTHREA-PM58OQJH';
        phive('Cashier/Fr')->instadebitFullnameMismatch($user, $instadebit_id);
        sleep(2);
        if (empty(Phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name ='$trigger' AND user_id = $uid"))) {
            echo 'OK' . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!";
        }
    }


    function testRG23()
    {

        $trigger = "RG23";
        $user_1 = cu('devtestnl');
        $user_2 = cu('devtestpl');
        $uid1 = $user_1->getId();
        $uid2 = $user_2->getId();
        $this->deleteTrigger($trigger, $uid1);
        $ip = '11.11.11.11';
        $fingerprint = uniqid();
        $session_1 = [
            'created_at' => '2019-04-01 08:41:40',
            'updated_at' => '2019-04-02 07:53:14',
            'ended_at' => '2019-04-02 07:53:14',
            'equipment' => 'PC',
            'end_reason' => 'logout',
            'user_id' => $uid1,
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'ip_classification_code' => ''
        ];

        phive('SQL')->sh($uid1)->insertArray('users_sessions', $session_1);
        sleep(3);
        $session_2 = [
            'created_at' => '2018-06-01 08:41:40',
            'updated_at' => '2018-06-02 07:53:14',
            'ended_at' => '2018-06-02 07:53:14',
            'equipment' => 'PC',
            'end_reason' => 'logout',
            'user_id' => $uid2,
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'ip_classification_code' => ''
        ];
        sleep(3);
        phive('SQL')->sh($uid2)->insertArray('users_sessions', $session_2);
        sleep(5);
        $user_2->setAttr('active', 0);
        sleep(5);
        phive('Cashier/Rg')->onLogin($uid1);
        sleep(5);
        $first_trigger = phive('SQL')->sh($uid1)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$uid1 ORDER BY  id  DESC");
        if (!empty($first_trigger)) {
            echo "OK, trigger id {$first_trigger['id']} " . chr(10);
        } else {
            echo 'ERROR!!!!!!!!!!!!!';
        }
        sleep(3);
        $no_trigger = phive('SQL')->sh($uid1)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id =$uid1 ORDER BY  id  DESC");
        sleep(1);
        if ($first_trigger['created_at'] == $no_trigger['created_at'] && $first_trigger['id'] == $no_trigger['id']) {
            echo "OK ,just one trigger per ip andfingerprint, trigger ->id  {$first_trigger['id']} " . chr(10);
        } else {
            echo "ERROR!!!!!!!!!!!!!!!";
        }
        sleep(1);
        $user_3 = cu('devtestde');
        $uid3 = $user_3->getId();
        $session_3 = [
            'created_at' => '2018-06-01 08:41:40',
            'updated_at' => '2018-06-02 07:53:14',
            'ended_at' => '2018-06-02 07:53:14',
            'equipment' => 'PC',
            'end_reason' => 'logout',
            'user_id' => $uid3,
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'ip_classification_code' => ''
        ];
        phive('SQL')->sh($uid3)->insertArray('users_sessions', $session_3);
        $user_3->setAttr('active', 0);
        sleep(3);
        phive('Cashier/Rg')->onLogin($uid1);
        sleep(4);
        Phive('SQL')->sh($uid1)->getValue("SELECT count(*) FROM triggers_log WHERE trigger_name = '$trigger'  AND user_id =$uid1 ");
        $number_triggers = Phive('SQL')->sh($uid1)->getValue("SELECT count(*) FROM triggers_log WHERE trigger_name = '$trigger'  AND user_id =$uid1 ");
        if ($number_triggers == 2) {
            echo "OK ,$number_triggers triggers " . chr(10);
        } else {
            echo 'ERROR!!!!!!!!!!!!';
        }
        $user_3->setAttr('active', 1);
        $user_2->setAttr('active', 1);

    }

    /**
     * RG20 for the Sportsbook
     * Customer lost more than €5000 in 30 days
     *
     * @param string $country
     *
     * @return void
     */
    function testLostMoreThanXEuroInTheLastXDaysAtTheSportsbook(string $country = 'GB')
    {
        $trigger = 'RG20';
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $sport_transactions = [
            [
                "ext_id" => "782edcc4-c4a5-40c9-9c8d-cc1e8f03dbde",
                "user_id" => $user_id,
                "ticket_id" => 203067,
                "ticket_type" => "single",
                "ticket_settled" => 1,
                "settled_at" => "2023-06-05T07:47:02.000Z",
                "amount" => 500000,
                "currency" => "EUR",
                "balance" => -100000,
                "bet_type" => "bet",
                "result" => 0,
            ],
            [
                "ext_id" => "fb3ffc04-4d45-4fd2-9e74-fc89f7f598d0",
                "user_id" => $user_id,
                "ticket_id" => 203064,
                "ticket_type" => "single",
                "ticket_settled" => 1,
                "settled_at" => "2023-06-05T07:19:02.000Z",
                "amount" => 500000,
                "currency" => "EUR",
                "balance" => 400000,
                "bet_type" => "bet",
                "result" => 0,
            ],
            [
                "ext_id" => "07e28ec7-3a4e-4598-a28c-091130e86eb7",
                "user_id" => $user_id,
                "ticket_id" => 203063,
                "ticket_type" => "single",
                "ticket_settled" => 1,
                "settled_at" => "2023-06-05T07:03:01.000Z",
                "amount" => 100000,
                "currency" => "EUR",
                "balance" => 300000,
                "bet_type" => "win",
                "result" => 1,
            ],
            [
                "ext_id" => "6aabaa5e-6551-4eb0-bd95-933c2bd7a60d",
                "user_id" => $user_id,
                "ticket_id" => 203056,
                "ticket_type" => "single",
                "ticket_settled" => 1,
                "settled_at" => "2023-06-05T06:48:02.000Z",
                "amount" => 100000,
                "currency" => "EUR",
                "balance" => 200000,
                "created_at" => "2023-06-05T06:48:02.000Z",
                "bet_type" => "win",
                "result" => 1,
            ],
            [
                "ext_id" => "f4ba7a66-aa92-4621-b462-036ec9373340",
                "user_id" => $user_id,
                "ticket_id" => 203048,
                "ticket_type" => "single",
                "ticket_settled" => 1,
                "settled_at" => "2023-06-05T06:24:03.000Z",
                "amount" => 100000,
                "currency" => "EUR",
                "balance" => 100000,
                "bet_type" => "win",
                "result" => 1,
            ],
            [
                "ext_id" => "546c0547-9df4-4ecb-8362-90885b412982",
                "user_id" => $user_id,
                "ticket_id" => 203044,
                "ticket_type" => "single",
                "ticket_settled" => 1,
                "settled_at" => "2023-06-05T05:56:02.000Z",
                "amount" => 100000,
                "currency" => "EUR",
                "balance" => 0,
                "bet_type" => "win",
                "result" => 1,
            ],
        ];
        phive('SQL')->insert2DArr('sport_transactions', array_values($sport_transactions));
        phive('Cashier/Rg')->lostMoreThanXEuroInTheLastXDaysAtTheSportsbook(phive()->today());
        sleep(3);
        $log = phive('SQL')->sh($user_id)->loadArray("SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = $user_id");
        $info = "User ID: {$user_id}" . chr(10);

        if (!empty($log)) {
            echo "{$trigger} OK. Country {$country}. {$info} {$log[0]['descr']} ->> {$log[0]['data']}" . chr(10);
        } else {
            echo "{$trigger} ERROR! Country {$country}. {$info}";
        }

        $this->cleanupTestPlayer(
            $user_id,
            [
                'sport_transactions',
                'triggers_log',
                'actions' => 'target',
                'users_settings',
            ]
        );
    }


    function deleteTrigger($trigger, $uid)
    {
        phive('SQL')->sh($uid)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $uid]);

    }

    /**
     * Overview: Compare lifetime average deposit amount per transaction to current day
     * and flag if average transaction sum has increased with 500%.
     * https://videoslots.sharepoint.com/sites/VSProduct/SitePages/RG-Flags.aspx#rg10-change-in-deposit-pattern
     *
     * @param $country
     *
     * @return bool
     */
    function testRG10($country = "MT")
    {
        $trigger = 'RG10';
        $user = $this->getTestPlayer($country);
        $user_grs_score = 100;
        $user_id = $user->getId();
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        $deposits = [
            ['amount' => 10000, 'sub_days' => 2],
            ['amount' => 10000, 'sub_days' => 1],
            ['amount' => 60000, 'sub_days' => 0],
            ['amount' => 60000, 'sub_days' => 0],
        ];

        foreach ($deposits as $deposit) {
            $insert = [
                'user_id' => $user_id,
                'amount' => $deposit['amount'],
                'dep_type' => "trustly",
                'timestamp' => phive()->hisMod("- {$deposit['sub_days']} day"),
                'ext_id' => uniqid(),
                'scheme' => "",
                'card_hash' => "",
                'loc_id' => "",
                'status' => "approved",
                'currency' => "EUR",
                'display_name' => "Trustly",
                'ip_num' => $user->getAttr('cur_ip'),
                'mts_id' => 0,
            ];
            phive('SQL')->sh($user_id, '', 'deposits')->insertArray('deposits', $insert);
        }
        sleep(5);
        phive('Cashier/Rg')->changeDepositPattern($user_id, $user_grs_score, phive()->today());
        sleep(10);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $user_id");
        $info = "User ID: {$user_id}" . chr(10);
        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'deposits',
                'users_realtime_stats',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "$trigger ERROR! $info";
            return false;
        }

        echo "{$trigger} trigger OK: " . json_encode($triggers) . chr(10);
        return true;
    }

    /**
     * Overview: Compare lifetime average deposit transactions per active
     * day to current day and flag if average transactions sum has increased with 500%.
     * https://videoslots.sharepoint.com/sites/VSProduct/SitePages/RG-Flags.aspx#rg11-change-in-deposit-pattern
     *
     * @param $country
     *
     * @return bool
     */
    function testRG11($country = "MT")
    {
        $trigger = 'RG11';
        $user = $this->getTestPlayer($country);
        $user_grs_score = 100;
        $user_id = $user->getId();
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        $deposits = [
            ['amount' => 10000, 'sub_days' => 1],
            ['amount' => 10000, 'sub_days' => 0],
            ['amount' => 10000, 'sub_days' => 0],
            ['amount' => 10000, 'sub_days' => 0],
            ['amount' => 10000, 'sub_days' => 0],
            ['amount' => 10000, 'sub_days' => 0],
            ['amount' => 10000, 'sub_days' => 0],
        ];

        foreach ($deposits as $deposit) {
            $insert = [
                'user_id' => $user_id,
                'amount' => $deposit['amount'],
                'dep_type' => "trustly",
                'timestamp' => phive()->hisMod("- {$deposit['sub_days']} day"),
                'ext_id' => uniqid(),
                'scheme' => "",
                'card_hash' => "",
                'loc_id' => "",
                'status' => "approved",
                'currency' => "EUR",
                'display_name' => "Trustly",
                'ip_num' => $user->getAttr('cur_ip'),
                'mts_id' => 0,
            ];
            phive('SQL')->sh($user_id, '', 'deposits')->insertArray('deposits', $insert);
        }
        sleep(5);
        phive('Cashier/Rg')->changeDepositPattern($user_id, $user_grs_score, phive()->today());
        sleep(10);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $user_id");
        $info = "User ID: {$user_id}" . chr(10);
        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'deposits',
                'users_realtime_stats',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "$trigger ERROR! $info";
            return false;
        }

        echo "{$trigger} trigger OK: " . json_encode($triggers) . chr(10);
        return true;
    }

    /**
     * Overview: Compare lifetime average deposit sum per active day to current day and flag if
     * deposit sum on current day have increased with X%.
     * https://videoslots.sharepoint.com/sites/VSProduct/SitePages/RG-Flags.aspx#rg12-change-in-deposit-pattern
     *
     * @param $country
     *
     * @return bool
     */
    function testRG12($country = "MT")
    {
        $trigger = 'RG12';
        $user = $this->getTestPlayer($country);
        $user_grs_score = 100;
        $user_id = $user->getId();
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        $deposits = [
            ['amount' => 10000, 'sub_days' => 2],
            ['amount' => 10000, 'sub_days' => 1],
            ['amount' => 60000, 'sub_days' => 0],
        ];
        foreach ($deposits as $deposit) {
            $insert = [
                'user_id' => $user_id,
                'amount' => $deposit['amount'],
                'dep_type' => "trustly",
                'timestamp' => phive()->hisMod("- {$deposit['sub_days']} day"),
                'ext_id' => uniqid(),
                'scheme' => "",
                'card_hash' => "",
                'loc_id' => "",
                'status' => "approved",
                'currency' => "EUR",
                'display_name' => "Trustly",
                'ip_num' => $user->getAttr('cur_ip'),
                'mts_id' => 0,
            ];
            phive('SQL')->sh($user_id, '', 'deposits')->insertArray('deposits', $insert);
        }
        sleep(5);
        phive('Cashier/Rg')->changeDepositPattern($user_id, $user_grs_score, phive()->today());
        sleep(10);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $user_id");
        $info = "User ID: {$user_id}" . chr(10);
        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'deposits',
                'users_realtime_stats',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "$trigger ERROR! $info";
            return false;
        }

        echo "{$trigger} trigger OK: " . json_encode($triggers) . chr(10);
        return true;
    }

    /**
     * Checking passing border between Low Risk and Medium Risk
     *
     * @return void
     */
    function testAML49()
    {
        $trigger = 'AML49';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];

        $this->deleteTrigger($trigger, $uid);
        $grs_log = [
            'user_id' => $uid,
            'rating_type' => 'AML',
            'rating' => 65,
            'rating_tag' => 'Low Risk',
        ];
        phive('SQL')->sh($uid)->delete('risk_profile_rating_log', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('users_daily_stats', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('users_game_sessions', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->save('risk_profile_rating_log', $grs_log);
        sleep(1);

        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 1,
            'deposits' => 9000001,
            'gross' => 5000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d'),
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        phive('Casino')->depositCash('devtestnl', 136014, 'Neosurf', uniqid(), 'Neosurf');
        sleep(2);
        $triggers = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");

        if (!empty($triggers)) {
            echo " trigger  ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "ERROR!!!!" . chr(10);
        }
    }

    /**
     * Checking passing border between Medium Risk and High Risk
     *
     * @return void
     */
    function testAML50()
    {
        $trigger = 'AML50';
        $u = (cu('devtestnl'));
        $user = $u->data;
        $uid = $user['id'];

        $this->deleteTrigger($trigger, $uid);
        $grs_log = [
            'user_id' => $uid,
            'rating_type' => 'AML',
            'rating' => 75,
            'rating_tag' => 'Medium Risk',
        ];
        phive('SQL')->sh($uid)->delete('risk_profile_rating_log', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('users_daily_stats', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->delete('users_game_sessions', ['user_id' => $uid]);
        phive('SQL')->sh($uid)->save('risk_profile_rating_log', $grs_log);
        sleep(1);

        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 70000000,
            'deposits' => 9999900,
            'gross' => 5000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d'),
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        $uds = [
            'user_id' => $uid,
            'country' => 'nl',
            'username' => $user['username'],
            'currency' => $user ['currency'],
            'bets' => 1,
            'deposits' => 9000001,
            'gross' => 5000000,
            'affe_id' => '0',
            'firstname' => 'Ben',
            'lastname' => 'Stiller',
            'aff_rate' => 0,
            'date' => date('Y-m-d'),
        ];
        $new_id = phive('SQL')->sh($uid)->save('users_daily_stats', $uds);
        phive('Casino')->depositCash('devtestnl', 136014, 'Neosurf', uniqid(), 'Neosurf');
        sleep(2);
        $triggers = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");

        if (!empty($triggers)) {
            echo "{$trigger} trigger  ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "{$trigger} ERROR!!!!" . chr(10);
        }
    }

    /**
     * Triggers on:
     * - cancellation of pending withdraws for 3 times/6 times and X times
     * - User has GRS score between 80-100.
     *
     * We can't trigger this flag from automation scripts or CLI commands because
     * under the hood we use authenticated (logged in) users.
     * In other words the system is sensitive to the 'initiator of disapproving of pending withdrawal'
     * The table pending_withdrawals has column approved_by and user_id
     * user_id is always known;
     * approved_by - is set only when the action initiated by the auth user and empty in case of automation/cli running
     * Due to this we can't fetch amount of disapproved pending withdrawals \Rg::getCancelledWithdrawals()
     *
     * @param $uid
     *
     * @return void
     */
    function testRG26($uid)
    {
        $trigger = 'RG26';
        $u = cu($uid);
        $net_account = 'test@test.com';
        $deposit_ext_id = 'test';
        phive('SQL')->sh($uid)->delete('deposits', ['user_id' => $uid, 'ext_id' => $deposit_ext_id]);
        phive('SQL')->sh($uid)->delete('pending_withdrawals', ['user_id' => $uid, 'net_account' => $net_account]);
        phive('SQL')->sh($uid)->delete('triggers_log', ['user_id' => $uid, 'trigger_name' => $trigger]);
        phive('Casino')->depositCash($uid, 90000, 'wirecard', $deposit_ext_id, 'visa', '3243 56** **** 1234', 122);
        for ($n = 0; $n < 3; $n++) {
            $withdrawal_id = phive('Cashier')->insertPendingCommon($u, 30000,
                ['net_account' => $net_account, 'payment_method' => 'neteller']);
            $result = phive('Cashier')->disapprovePending($withdrawal_id);
            sleep(1);
        }
        sleep(2);
        $triggers = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        if (!empty($triggers)) {
            echo "{$trigger} trigger  ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "{$trigger} ERROR!!!!" . chr(10);
        }
    }

    /**
     * RG40 - For when customers go over their selected amounts in a monthly basis.
     *
     * @param $uid
     *
     * @return void
     */
    function testRG40($uid)
    {
        $trigger = 'RG40';
        $day = phive()->today();
        $u = cu($uid);
        $uid = $u->getId();
        phive('SQL')->sh($uid)->delete('triggers_log', ['user_id' => $uid, 'trigger_name' => $trigger]);
        phive('SQL')->query(
            "DELETE FROM users_realtime_stats WHERE user_id = '$uid' AND MONTH(date) = MONTH('$day') AND YEAR(date) = YEAR('$day')"
        );
        $intended_gambling_max = 100;
        $u->setSetting('intended_gambling', "0–{$intended_gambling_max}");
        $insert = [
            'date' => $day,
            'user_id' => $uid,
            'currency' => 'EUR',
            'bets' => 11000,
            'wins' => 0,
            'rewards' => 0,
            'fails' => 0,
            'jp_contrib' => 0,
        ];

        phive('SQL')->sh($uid)->insertArray('users_realtime_stats', $insert);
        phive()->pexec('Cashier/Rg', 'invoke', ['intendedGambling', $day]);
        sleep(5);
        $triggers = phive('SQL')->sh($uid)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $uid");
        if (!empty($triggers)) {
            echo "{$trigger} trigger  ok id , -> {$triggers['id']} " . chr(10);
        } else {
            echo "{$trigger} ERROR!!!!" . chr(10);
        }

    }

    /**
     * RG37 - user's RG GRS breached threshold 80 (config name RG37)
     *
     * @return bool
     */
    function testRG37()
    {
        $trigger = 'RG37';
        $user = $this->getTestPlayer();
        $user_id = $user->getId();
        $body = ['user_id' => $user_id, 'jurisdiction' => $user->getJurisdiction()];
        $response = phive()->postToBoApi("/risk-profile-rating/calculate-score/all", $body);
        $updated_scores = json_decode($response, true);
        echo "<pre>";
        print_r($updated_scores);
        sleep(5);

        $interval = DateInterval::createFromDateString('16 years');
        $dob = (new DateTime('now'))->sub($interval);
        $user->updateData(['dob' => $dob->format('Y-m-d')]);
        phive('Casino')->depositCash($user_id, 10000, 'trustly', uniqid());
        sleep(15);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $user_id");
        $info = "User ID: {$user_id}" . chr(10);
        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "$trigger ERROR! $info";
            return false;
        }

        echo "{$trigger} trigger OK: " . json_encode($triggers) . chr(10);
        return true;
    }

    /**
     * RG27 - user's RG GRS between 80 - 100 (config name RG27)
     *
     * @return bool
     */
    function testRG27()
    {
        $trigger = 'RG27';
        $user = $this->getTestPlayer();
        $user_id = $user->getId();
        $interval = DateInterval::createFromDateString('16 years');
        $dob = (new DateTime('now'))->sub($interval);
        $user->updateData(['dob' => $dob->format('Y-m-d')]);
        $body = ['user_id' => $user_id, 'jurisdiction' => $user->getJurisdiction()];
        $response = phive()->postToBoApi("/risk-profile-rating/calculate-score/all", $body);
        $updated_scores = json_decode($response, true);
        sleep(5);
        echo "<pre>";
        print_r($updated_scores);
        phive('Casino')->depositCash($user_id, 10000, 'trustly', uniqid());
        sleep(15);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = '$trigger' AND user_id = $user_id");
        $info = "User ID: {$user_id}" . chr(10);
        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "$trigger ERROR! $info";
            return false;
        }

        echo "{$trigger} trigger OK: " . json_encode($triggers) . chr(10);
        return true;
    }

    /**
     * On login: 3 or more self-lock within a 90-day period
     *
     * @param string $country
     *
     * @return bool
     */
    public function testRG6(string $country = 'GB'): bool
    {
        $trigger = 'RG6';
        $numDays = 1;
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();

        for ($n = 0; $n < 3; $n++) {
            $rg_limits_actions = phive('DBUserHandler/RgLimitsActions')->setUserObject($user);
            $rg_limits_actions->lock($numDays);
            // to expire the unlock-date intentionally
            $unlock_at = phive()->today();
            $user->setSetting('unlock-date', $unlock_at);
            sleep(2);

            phive("UserHandler")->unlockLocked();
        }
        // pretend to be user. Note: Rg6 is not triggered on lock actions initiated by admin or system
        phive('SQL')->sh($user_id, 'target', 'actions')->updateArray('actions', ['actor' => $user_id],
            ['target' => $user_id, 'tag' => 'lock-date']);
        phive('Cashier/Rg')->closedVsReOpenRate($user);
        sleep(3);
        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}");
        $info = "User ID: {$user_id}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($triggers)) {
            echo "{$trigger} ERROR! {$info}";
            return false;
        }

        echo "{$trigger} OK -> " . json_encode($triggers) . chr(10);
        return true;
    }

    /**
     * Limit (deposit, wager) number of changes >= 3 in 30 days
     *
     * @param string $country
     *
     * @return bool
     */
    function testRG9(string $country = 'MT'): bool
    {
        $trigger = 'RG9';
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $thold = phive('Config')->getValue('RG', $trigger, 3);
        echo "{$trigger} threshold {$thold} for user {$user_id} \n\n";

        // Add deposit limits
        echo "add deposit limits for user {$user_id} \n";
        rgLimits()->addLimit($user, 'deposit', 'day', 10000);
        rgLimits()->addLimit($user, 'deposit', 'week', 200000);
        rgLimits()->addLimit($user, 'deposit', 'month', 300000);
        $lowering_amount = 500;

        for ($n = 0; $n <= $thold; $n++) {
            echo "change deposit limit for user {$user_id} n#:{$n} \n";
            $limits = phive('SQL')->sh($user_id)->loadArray("
            SELECT
                rl.cur_lim,
                rl.new_lim,
                rl.time_span
            FROM
                rg_limits rl
            WHERE
                rl.user_id = {$user_id}
                AND rl.type IN ('deposit')
            ORDER BY
                rl.id ASC");
            echo "limit day {$limits[0]['cur_lim']} | week {$limits[1]['cur_lim']} | month {$limits[2]['cur_lim']} n#:{$n} \n";
            rgLimits()->saveLimit($user, 'deposit', 'day', ($limits[0]['cur_lim'] - $lowering_amount));
            rgLimits()->saveLimit($user, 'deposit', 'week', ($limits[1]['cur_lim'] - $lowering_amount));
            rgLimits()->saveLimit($user, 'deposit', 'month', ($limits[2]['cur_lim'] - $lowering_amount));

            $limits_to_update = phive('SQL')->sh($user_id)->loadArray("
            SELECT a.id
            FROM
                actions a
            WHERE
                a.target = {$user_id}
                AND a.tag like 'deposit-rgl-%'
                OR a.tag like 'wager-rgl-%'
                AND a.created_at > (NOW() - INTERVAL 30 MINUTE)");
            $ids = implode(', ', array_column($limits_to_update, 'id'));

            phive('SQL')->sh($user_id)->query(
                "UPDATE actions a
            SET a.actor = {$user_id}
            WHERE a.id IN ({$ids})");

            phive('Cashier/Arf')->invoke('onSetLimit', $user);
            sleep(2);
        }

        echo "\ncheck {$trigger} for {$user_id} \n";
        $triggers = phive('SQL')->sh($user_id)->loadAssoc("
        SELECT
            tl.*
        FROM
            triggers_log tl
        WHERE
            tl.user_id = {$user_id}
            and tl.trigger_name = '{$trigger}'
            and tl.created_at > (NOW() - INTERVAL 30 MINUTE)
        ORDER by
            tl.id DESC");
        $info = "User ID: {$user_id}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'risk_profile_rating_log',
                'rg_limits',
            ]
        );

        if (empty($triggers)) {
            echo "{$trigger} ERROR! {$info}";
            return false;
        }

        echo "{$trigger} OK -> " . json_encode($triggers) . chr(10);
        return true;
    }

    /**
     * Tests RG64 High Deposit Frequency Withing 24 hours
     *
     * @param bool $flag_turned_on
     *
     * @return bool
     */
    public function testRG64(bool $flag_turned_on = true): bool
    {
        $trigger_name = 'RG64';

        $sql = Phive('SQL');

        $user = (cu('devtestmt'));
        $user_id = $user->getId();

        if (empty($user)) {
            echo "User not found\n";
            return false;
        }

        $config = phive('Config');
        $pre_trigger_config_state = $config->getValue('RG', "{$trigger_name}-high-deposit-frequency");
        $config = Phive('Config');
        $pre_trigger_config_threshold = $config->getValue('RG', "{$trigger_name}-high-deposit-frequency-threshold");
        if (empty($pre_trigger_config_state) || $pre_trigger_config_threshold == "") {
            echo "Migration did not ran prior to testing\n";
            return false;
        }

        $config_threshold = 5;
        $sql->shs()->updateArray(
            'config',
            ['config_value' => $config_threshold],
            ['config_name' => "{$trigger_name}-high-deposit-frequency-threshold"]
        );

        $flag_value = $flag_turned_on ? 'on' : 'off';
        $sql->shs()->updateArray(
            'config',
            ['config_value' => $flag_value],
            ['config_name' => "{$trigger_name}-high-deposit-frequency"]
        );

        $asserts = [
            'on' => [
                'test1' => "Flag Triggered",
                'test2' => "Flag Triggers Only Once",
                'test3' => "Flag Triggered"
            ],
            'off' => [
                'test1' => "Flag did not trigger",
                'test2' => "Flag triggered 0 times",
                'test3' => "Flag did not trigger"
            ]
        ];

        $deposit_ids = [];
        $random = rand(100000000, 900000000);
        for ($i = 0; $i <= $config_threshold + 1; $i++) {
            $deposit_id = $sql->sh($user_id)->insertArray('deposits', [
                'amount' => 2000,
                'user_id' => $user_id,
                'dep_type' => 'neteller',
                'timestamp' => phive()->hisMod('-10 minute'),
                'status' => 'approved',
                'ext_id' => $random = $random + 1,
                'scheme' => 123,
                'card_hash' => 123,
                'loc_id' => 123,
                'ip_num' => 123,
                'display_name' => 123,
            ]);
            $deposit_ids[] = $deposit_id;
        }

        $today = phive()->today();
        $trigger_check_sql = "
           SELECT id
           FROM triggers_log
           WHERE user_id = {$user_id}
             AND trigger_name = '{$trigger_name}'
             AND DATE(created_at) = '{$today}';
       ";

        echo "Test 1: Checking if flag triggers\n";
        phive('Cashier/Rg')->highDepositFrequency($user);
        $trigger_result = $sql->sh($user_id)->loadArray($trigger_check_sql);
        $test1 = !empty($trigger_result) ? "Flag Triggered" : "Flag did not trigger";
        echo $test1 . " - ";
        $test1_result = ($test1 === $asserts[$flag_value]['test1']);
        echo $test1_result ? "PASS\n" : "FAIL\n";

        echo "Test 2: Checking if flag not triggers again after it already triggered within one day\n";
        phive('Cashier/Rg')->highDepositFrequency($user);
        $trigger_result = $sql->sh($user_id)->loadArray($trigger_check_sql);
        $trigger_count = count($trigger_result);
        $test2 = $trigger_count === 1 ?
            "Flag Triggers Only Once" : "Flag triggered " . count($trigger_result) . " times";
        echo $test2 . " - ";
        $test2_result = $test2 === $asserts[$flag_value]['test2'];
        echo $test2_result ? "PASS\n" : "FAIL\n";

        // Cleanup
        $sql->sh($user_id)->query("
           DELETE
           FROM triggers_log
           WHERE user_id = {$user_id}
             AND trigger_name = '{$trigger_name}'
             AND DATE(created_at) = '{$today}';
       ");

        echo "Test 3: Checking if flag triggers after it already triggered beyond one day\n";
        $triggers_log_id = $sql->sh($user_id)->insertArray('triggers_log', [
            'user_id' => $user_id,
            'trigger_name' => $trigger_name,
            'created_at' => phive()->hisMod('-2 day'),
            'descr' => '',
            'data' => '',
            'cnt' => 0,
            'txt' => ''
        ]);
        phive('Cashier/Rg')->highDepositFrequency($user);
        $trigger_result = $sql->sh($user_id)->loadArray($trigger_check_sql);
        $test3 = !empty($trigger_result) ? "Flag Triggered" : "Flag did not trigger";
        echo $test3 . " - ";
        $test3_result = $test3 === $asserts[$flag_value]['test3'];
        echo $test3_result ? "PASS\n" : "FAIL\n";

        // Cleanup
        $sql->shs()->updateArray(
            'config',
            ['config_value' => $pre_trigger_config_threshold],
            ['config_name' => "{$trigger_name}-high-deposit-frequency-threshold"]
        );

        $sql->shs()->updateArray(
            'config',
            ['config_value' => $pre_trigger_config_state],
            ['config_name' => "{$trigger_name}-high-deposit-frequency"]
        );

        $sql->sh($user_id)->query("
           DELETE
           FROM triggers_log
           WHERE user_id = {$user_id}
             AND trigger_name = '{$trigger_name}'
             AND DATE(created_at) = '{$today}';
       ");
        $sql->sh($user_id)->query("DELETE FROM triggers_log WHERE id = {$triggers_log_id};");
        foreach ($deposit_ids as $deposit_id) {
            $sql->sh($user_id)->query("DELETE FROM deposits WHERE id = {$deposit_id};");
        }

        echo "Final Result:\n";
        if ($test1_result && $test2_result && $test3_result) {
            echo "PASS\n";
            return true;
        } else {
            echo "FAIL\n";
            return false;
        }
    }


    /**
     * AML32 > or = 5 IP linked accounts from sign up
     *
     * @return bool
     */
    function testAML32(): bool
    {
        $trigger = 'AML32';
        $user = $this->getTestPlayer();
        $user_id = $user->getId();
        $reg_ip = $user->getAttribute('reg_ip');
        $config = phive('SQL')->sh($user_id)->loadAssoc("SELECT * FROM config WHERE config_name = '{$trigger}' LIMIT 1;");
        $thold = $config['config_value'];
        $users_to_update = phive('SQL')
            ->shs()
            ->loadArray("SELECT u.id, u.reg_ip FROM users u
                      WHERE u.id != {$user_id}
                      AND NOT EXISTS (
                            SELECT u_s.user_id FROM users_settings AS u_s
                            WHERE u.id = u_s.user_id
                              AND (
                                  (u_s.setting = 'registration_in_progress' AND u_s.value >= 1)
                                      OR (u_s.setting = 'test_account' AND u_s.value = 1)
                                  )
                        ) ORDER BY u.register_date DESC LIMIT {$thold}");
        $users_to_update = array_slice($users_to_update, 0, $thold, true);

        foreach ($users_to_update as $user_to_update) {
            phive('SQL')->sh($user_to_update['id'], '', 'users')->updateArray(
                'users',
                [
                    'reg_ip' => $reg_ip,
                ],
                "id={$user_to_update['id']}"
            );
        }
        sleep(5);
        phive("Cashier/Fr")->ipLinks($user);
        foreach ($users_to_update as $user_to_update) {
            phive('SQL')->sh($user_to_update['id'], '', 'users')->updateArray(
                'users',
                [
                    'reg_ip' => $user_to_update['reg_ip'],
                ],
                "id={$user_to_update['id']}"
            );
        }
        sleep(3);
        $trigger_log = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}");
        $info = "User ID: {$user_id}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'deposits',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (empty($trigger_log)) {
            echo "{$trigger} ERROR! {$info}";
            return false;
        }

        echo "{$trigger} OK -> " . json_encode($trigger_log) . chr(10);
        return true;
    }

    /**
     * RG65
     *  Mark users that met the condition:
     *  users younger/older than age N,
     *  for X consequence days (starting balance + deposits - withdrawals - end balance) >= (net loss thold)
     *
     * @return bool
     * @throws Exception
     */
    public function testRG65(): bool
    {
        $trigger = 'RG65';
        $country = "ES";
        $user = $this->getTestPlayer($country, 26);
        $user_id = $user->getId();
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('external_regulatory_user_balances', ['user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('users_daily_stats', ['user_id' => $user_id]);
        $license = phive("Licensed/{$country}/{$country}");
        $setting = $license->getLicSetting('intensive_gambling_signs')[0];
        $balance_in_cents = 60000;
        for ($n = 1; $n <= $setting['consecutive_weeks']; $n++) {
            $start_date = Carbon::parse('now')->subWeeks($n)->startOfWeek(CarbonInterface::MONDAY);
            $end_date = Carbon::parse($start_date)->endOfWeek(CarbonInterface::SUNDAY);
            // 1. insert start & end balance for N consecutive weeks
            $start_balance = [
                "user_id" => $user_id,
                "balance_date" => $start_date->copy()->subDay()->toDateString(),
                "cash_balance" => $n === 1 ? $balance_in_cents : $balance_in_cents += 30000,
                "bonus_balance" => 0,
                "extra_balance" => 0,
                "currency" => "EUR",
            ];
            phive('SQL')->sh($user_id)->insertArray('external_regulatory_user_balances', $start_balance);

            if ($n === 1) {
                $end_balance = [
                    "user_id" => $user_id,
                    "balance_date" => $end_date->toDateString(),
                    "cash_balance" => 30000,
                    "bonus_balance" => 0,
                    "extra_balance" => 0,
                    "currency" => "EUR",
                ];
                phive('SQL')->sh($user_id)->insertArray('external_regulatory_user_balances', $end_balance);
            }

            // 2. insert deposits and withdrawals for N consecutive weeks
            $users_daily_stats = [
                'username' => $user->getUsername(),
                'affe_id' => rand(100000, 900000000),
                'firstname' => $user->data['firstname'],
                'user_id' => $user_id,
                'aff_rate' => 0.5,
                'lastname' => $user->data['lastname'],
                'deposits' => 100000,
                'withdrawals' => 50000,
                "currency" => "EUR",
                "country" => $country,
                'date' => $end_date->toDateString(),

            ];
            phive('SQL')->sh($user_id)->insertArray('users_daily_stats', $users_daily_stats);
        }
        sleep(15);
        $license->onMondayMidnight();
        sleep(30);

        $triggers = phive('SQL')->sh($user_id)->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}");
        $info = "User ID: {$user_id}" . chr(10);

        if (empty($triggers)) {
            echo "{$trigger} ERROR! {$info}";
            return false;
        }

        echo "{$trigger} OK -> " . json_encode($triggers) . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'deposits',
                'users_daily_stats',
                'external_regulatory_user_balances',
                'risk_profile_rating_log',
                'rg_limits',
            ]
        );

        return true;
    }

    /**
     * RG65
     *  Mark users that met the condition:
     *  users younger/older than age N,
     *  for X consequence days (starting balance + deposits - withdrawals - end balance) >= (net loss thold)
     *
     * @return bool
     * @throws Exception
     */
    public function testRG65Revocation(): bool
    {
        $trigger = 'RG65';
        $country = "ES";
        $user = $this->getTestPlayer($country);
        $user_id = $user->getId();
        $license = phive("Licensed/{$country}/{$country}");
        phive('UserHandler')->logTrigger(
            $user,
            $trigger,
            "Intensive Gambler detected."
        );
        sleep(5);
        $license->onMondayMidnight();
        sleep(10);

        $trigger_log = phive('SQL')->sh($user_id)
            ->loadAssoc(" SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}");
        $action = phive('SQL')->sh($user_id)
            ->loadAssoc(" SELECT * FROM actions WHERE tag = 'tis-flag-revoked' AND target = {$user_id}");
        $info = "User ID: {$user_id}" . chr(10);

        if (!empty($trigger_log) || empty($action)) {
            echo "{$trigger} ERROR! {$info}";
            return false;
        }

        echo "{$trigger} has been revoked OK" . json_encode($action) . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'deposits',
                'users_daily_balance_stats',
                'risk_profile_rating_log',
                'rg_limits',
            ]
        );
        return true;
    }


    /**
     * Singular or accumulated deposit of = or <€5,000 from sign up or within 24 - 48 hour time period
     *
     * Test flow:
     * - create test user from scratch
     * - remove entries from triggers_log and deposits
     * - create three deposits
     * - between deposit transaction we remove old deposits, modify trigger created_at timestamp
     *   to allow retrigger a flag
     * - expected that the flag should be triggered three times
     *
     * @return bool
     */
    public function testAML7(): bool
    {
        $trigger = 'AML7';
        $now = Carbon::now();
        $user = $this->getTestPlayer('MT');
        $user_id = $user->getId();
        $info = "User ID: {$user_id}" . chr(10);
        $tables = [
            'triggers_log',
            'actions' => 'target',
            'users_settings',
            'deposits',
            'risk_profile_rating_log',
        ];
        phive('SQL')->sh($user_id)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);

        foreach ([500000, 1000000, 500000] as $amount) {
            phive('Casino')->depositCash($user_id, $amount, 'trustly', uniqid());
            sleep(5);
            $created_at = $now->copy()->subDay()->toDateTimeString();
            phive('SQL')->sh($user_id)->query(
                "UPDATE triggers_log SET created_at = '{$created_at}'
                    WHERE trigger_name = '{$trigger}' AND user_id = {$user_id};"
            );
            phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);
            sleep(5);
        }
        sleep(5);
        $triggers = phive('SQL')->sh($user_id)->loadArray(
            "SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}"
        );
        $count = count($triggers);

        if (empty($triggers) || $count < 3) {
            echo "ERROR! {$info}. Expected 3 flags triggered. Got: {$count}" . chr(10);
            $this->cleanupTestPlayer(
                $user_id,
                $tables,
            );
            return false;
        }

        echo "OK {$info}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            $tables,
        );

        return true;
    }


    /**
     * 1k Single/Accumulated transaction using Paysafe/Flexepin/Neosurf/CashToCode
     * Singular or accumulated deposits of €1,000 or more within 30 days using
     * Paysafecard, Flexipin, Neosurf or CashToCode.
     *
     * @return bool
     */
    public function testAML57(): bool
    {
        $trigger = 'AML57';
        $user = $this->getTestPlayer('MT');
        $user_id = $user->getId();
        $info = "User ID: {$user_id}" . chr(10);
        $tables = [
            'triggers_log',
            'actions' => 'target',
            'users_settings',
            'deposits',
            'risk_profile_rating_log',
        ];
        $deposit_thold = phive('Config')->getValue('AML', "$trigger-deposit-thold", 100000);
        $psp = phive('Config')->getValue('AML', "$trigger-psp", "paysafe,flexepin,neosurf,cashtocode");
        $psp = explode(',', $psp);
        $deposit_amount = $deposit_thold - 5000;
        $descr = "The thold {$deposit_thold}, the deposits amount {$deposit_amount}";
        phive('Casino')->depositCash($user_id, $deposit_thold - 5000, $psp[0], uniqid());
        sleep(5);

        $triggers = phive('SQL')->sh($user_id)->loadArray(
            "SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}"
        );

        if (!empty($triggers)) {
            echo "ERROR! {$info} $trigger should not be triggered. $descr";
            $this->cleanupTestPlayer(
                $user_id,
                $tables,
            );
            return false;
        } else {
            echo "OK {$info} Flag $trigger wasn't triggered. $descr" . chr(10);
        }

        phive('SQL')->sh($user_id)->delete('triggers_log', ['trigger_name' => $trigger, 'user_id' => $user_id]);
        phive('SQL')->sh($user_id)->delete('deposits', ['user_id' => $user_id]);

        $deposits = [
            ['psp' => 'paysafe', 'amount' => 25000],
            ['psp' => 'flexepin', 'amount' => 25000],
            ['psp' => 'neosurf', 'amount' => 25000],
            ['psp' => 'cashtocode', 'amount' => 25000],
            ['psp' => 'neteller', 'amount' => 25000], // redundant, for test purpose
        ];
        foreach($deposits as $deposit) {
            phive('Casino')->depositCash($user_id, $deposit['amount'], $deposit['psp'], uniqid());
            sleep(10);
        }
        sleep(10);
        $triggers = phive('SQL')->sh($user_id)->loadArray(
            "SELECT * FROM triggers_log WHERE trigger_name = '{$trigger}' AND user_id = {$user_id}"
        );

        if (empty($triggers)) {
            echo "ERROR! {$info}";
            $this->cleanupTestPlayer(
                $user_id,
                $tables,
            );
            return false;
        }

        echo "OK {$info}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            $tables,
        );

        return true;
    }
}
