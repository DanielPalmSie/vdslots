<?php

use Laraphive\Domain\Content\DataTransferObjects\Requests\EventsRequestData;


class TestLicensed extends TestPhive
{
    function __construct($uid)
    {
        $this->db = phive('SQL');
        $this->ud = ud($uid);
        $this->uid = $uid;
        return $this;
    }

    /**
     * Creating the test for Session report in Dk
     *  creating the session in users_game_sessions table
     * sleep 5 seconds
     * force log_out  and end the session
     * and launch the report
     * check the structure with jar file provides for dk
     * and check if the file is send in the server...
     *
     */
    function TestLaunchSessionReport()
    {
        $final_file = $this->getFinalFile('KasinoSpil');
        $test = rand(1, 6);
        if ($final_file) {
            echo("The Folder is not removed");
            exit;
        }
        //Creating the Sessions...
        for ($i = 0; $i < $test; $i++) {

            $bet_amount = rand(50, 150);
            $count = rand(2, 30);
            $win = rand(0, 120);
            $win_count = rand(0, $count);
            $users_id = array(13063, 5129736, 5130056, 5133757, 5136335, 5482164);
            $user_id = $users_id[rand(0, 5)];
            $ins = array('user_id' => $user_id,
                'game_ref' => 'netent_starburst_mobile_html_sw',
                'device_type_num' => 1,
                'balance_start' => 100,
                'bet_cnt' => $count,
                'bet_amount' => $bet_amount,
                'win_amount' => $win,
                'session_id' => rand(0, 9999999),
                'ip' => '188.183.126.34',
            );

            $session = phive('Casino')->getGsess($ins);
            $session['win_amount'] = $win;
            $session['win_cnt'] = $win_count;
            $session['bet_cnt'] = $count;
            $session['bet_amount'] = $bet_amount;
            $this->db->sh($session, 'user_id', 'users_game_sessions')->save('users_game_sessions', $session);
            sleep(rand(8, 25));
            phive('DBUserHandler')->logoutUser($user_id);
            sleep(5);
        }
        phive('Licensed/DK/DK')->exportData('KasinoSpil', 'DK', '');
        sleep(60);
        $files = $this->getFinalFile('KasinoSpil', false);
        $params = Phive('Licensed/DK/DK')->extractParams();
        $SpilCertifikatIdentifikation = phive('Licensed/DK/DK')->getLicSetting('SpilCertifikatIdentifikation');
        if ($files[0]['name'] != "$SpilCertifikatIdentifikation-{$params['TamperTokenID']}-E.xml")
            echo('ERROR the Files is not  properly created');
        else  echo(' the process is OK');

    }

    function getFinalFile($type, $remove = true)
    {

        $ftp = new ImplicitFtp('192.168.30.65', 'spillemyndigheden', 'cZkPCYvBVtq45DgL', 990);
        $params = Phive('Licensed/DK/DK')->extractParams();
        $date = date('Y-m-d', strtotime($params['TamperTokenUdstedelseDatoTid']));
        $remoteDir = Phive('Licensed/DK/DK')->getFtpFolderName();
        $remoteDir .= $date;
        if ($remove) {
            $ftp->delete($remoteDir, '');
        }
        $SpilCertifikatIdentifikation = phive('Licensed/DK/DK')->getLicSetting('SpilCertifikatIdentifikation');
        $final_file = $ftp->list("$remoteDir/$SpilCertifikatIdentifikation-{$params['TamperTokenID']}/$type/" . date('Y-m-d'));
        return $final_file;

    }

    function testLaunchEndOfDayReport()
    {
        $final_file = $this->getFinalFile('EndOfDay');
        if ($final_file) {
            echo("The Folder is not removed");
            exit;
        }
        $date = phive()->yesterday();
        $sql = "SELECT DISTINCT(user_id) FROM videoslots.users_game_sessions INNER  JOIN  users ON users.id= users_game_sessions.user_id  WHERE country='DK' AND DATE(end_time)='$date'";
        $users = $this->db->shs()->loadArray($sql);
        foreach ($users as $user) {
            $user = $user['user_id'];
            $sql = "SELECT  username, affe_id,firstname,user_id , lastname , sum(bet_cnt) AS  bets_count ,sum(bet_amount) AS bets , sum(win_amount) AS wins , sum(win_cnt) AS wins_count, game_ref,DATE(end_time) AS DATE , users.currency ,users.country
                      ,device_type_num AS device_type  FROM videoslots.users_game_sessions INNER JOIN videoslots.users ON users.id=users_game_sessions.user_id WHERE  DATE(end_time)='$date'  AND user_id=$user";
            $sums = $this->db->sh($user, 'user_id', 'users_game_sessions')->loadArray($sql);

            foreach ($sums as $sum) {
                $this->db->insertArray('users_daily_game_stats', $sum);
            }
        }

        phive('Licensed/DK/DK')->exportData('EndOfDay', 'DK');
        sleep(65);
        $files = $this->getFinalFile('EndOfDay', false);
        $params = Phive('Licensed/DK/DK')->extractParams();
        $SpilCertifikatIdentifikation = phive('Licensed/DK/DK')->getLicSetting('SpilCertifikatIdentifikation');
        if ($files[count($files) - 1]['name'] != "$SpilCertifikatIdentifikation-{$params['TamperTokenID']}-E.xml")
            echo('ERROR the Files is not  properly created');
        else  echo(' the process is OK');
    }


    function testCloseTempAccs($u, $winnings = 9900, $set_temp_acc = true, $reg_date = '2012-01-01')
    {

        $this->db->truncate('mailer_queue');
        $this->deleteByUser($u, 'actions', 'users_settings', 'users_blocked', 'cash_transactions');
        if ($set_temp_acc) {
            $u->setSetting('temporary_account', 1);
        } else {
            $u->deleteSetting('temporary_account');
        }
        $u->setAttr('register_date', $reg_date);
        $dep_sum = phive('Cashier')->getUserDepositSum($user);
        if (empty($dep_sum)) {
            $dep_sum = 10000;
            phive('Casino')->depositCash($u, $dep_sum, 'neteller', '123');
        }
        $cash_balance = $dep_sum + $winnings;
        $u->setAttr('cash_balance', $cash_balance);
        lic('closeTempAccs', [], $u);
        $this->printLatest($u, 'actions');
        $this->printLatest($u, 'users_settings');
        $this->printLatest($u, 'users_blocked');
        $this->printLatest($u, 'cash_transactions');
        $this->printAll('mailer_queue');
        $u->refresh();
        echo "User: \n";
        print_r($u);
    }

    function testGameOverride($u_obj){
        $this->db->truncate('game_country_overrides');
        $g = phive('MicroGames')->getById(2322);
        echo "Before override:\n";
        $res = phive('MicroGames')->overrideGame($u_obj, $g);
        print_r($res);
        // We test with starburst desktop
        $this->db->save('game_country_overrides', [
            'game_id'        => $g['id'],
            'country'        => $u_obj->getCountry(),
            'ext_game_id'    => $g['ext_game_name'],
            'payout_percent' => 0.95
        ]);
        echo "After override:\n";
        $res = phive('MicroGames')->overrideGame($u_obj, $g);
        print_r($res);
    }

    function printExtra($res){
        print_r(['rtp' => $res['payout_percent'], 'launch_id' => $res['game_id'], 'ext_id' => $res['ext_game_name']]);
    }

    function testGetGame($game_name, $u_obj, $u_obj_other){
        $mg = phive('MicroGames');
        $games = $this->db->loadArray("SELECT * FROM micro_games WHERE game_name LIKE '%$game_name%' LIMIT 1");
        foreach(['new', ''] as $suffix){
            foreach($games as $g){
                $ext_id    = $g['ext_game_name'].$suffix;
                $launch_id = $g['game_id'].$suffix;
                $this->db->save('game_country_overrides', [
                    'game_id'        => $g['id'],
                    'country'        => $u_obj->getCountry(),
                    'ext_game_id'    => $ext_id,
                    'ext_launch_id'  => $launch_id,
                    'payout_percent' => 0.90
                ]);


                echo "The game, fetched by ext_game_name $ext_id for {$u_obj->getUsername()}\n";
                $res = $mg->getByGameRef($ext_id, 0, $u_obj);
                $this->printExtra($res);
                $mg->cur_game = null;

                echo "The game, fetched by ext_game_name $ext_id for {$u_obj_other->getUsername()}\n";
                $res = $mg->getByGameRef($ext_id, 0, $u_obj_other);
                $this->printExtra($res);
                $mg->cur_game = null;

                echo "The game, fetched by game_id $launch_id for {$u_obj->getUsername()}\n";
                $res = $mg->getByGameId($launch_id, 0, $u_obj);
                $this->printExtra($res);
                $mg->cur_game = null;

                echo "The game, fetched by game_id $launch_id for {$u_obj_other->getUsername()}\n";
                $res = $mg->getByGameId($launch_id, 0, $u_obj_other);
                $this->printExtra($res);
                $mg->cur_game = null;
            }
        }
    }

    function setupRtpBoxTest($u_obj, $game_name){
        $games = $this->db->loadArray("SELECT * FROM micro_games WHERE game_name LIKE '%$game_name%'");
        foreach($games as $g){
            $this->db->save('game_country_overrides', [
                'game_id'        => $g['id'],
                'country'        => $u_obj->getCountry(),
                'ext_game_id'    => $g['ext_game_name'],
                'payout_percent' => 0.90
            ]);
        }
    }

    function insertOverride($gid, $country, $rtp = '0.9'){
        $game = phive('MicroGames')->getById($gid);
        $insert = [
            'game_id'         => $gid,
            'country'         => $country,
            'ext_game_id'     => $game['ext_game_name'],
            'ext_launch_id'   => $game['game_id'],
            'payout_percent'  => $rtp,
            'device_type'     => $game['device_type'],
            'device_type_num' => $game['device_type_num']
        ];
        return $this->db->insertArray('game_country_overrides', $insert);
    }

    function setupBookOfRaClassic(){
        foreach([6297, 6167] as $gid){
            $this->insertOverride($gid, 'GB');
            $this->db->delete('micro_games', ['id' => $game['id']]);
        }
    }

    function setupBookOfDead(){
        foreach([4093, 3894] as $gid){
            $this->insertOverride($gid, 'SE');
        }
    }

    function testAPIEventFeed($u_to_test, $u_other){
        // Get random games from database
        $games = $this->db->loadArray("SELECT * FROM micro_games ORDER BY RAND() LIMIT 5");
        
        // Test events array with various event types
        $test_events = [
            ['tag' => 'woningame', 'amount' => 10000],
            ['tag' => 'freespins', 'amount' => 50],
            ['tag' => 'climbedlevel', 'amount' => 5, 'name' => 'Level Up'],
            ['tag' => 'awardedcashback', 'amount' => 500, 'name' => 'Cashback'],
            ['tag' => 'earnedcashback', 'amount' => 300, 'name' => 'Cashback Earned'],
            ['tag' => 'trophyreward', 'amount' => 1000, 'name' => 'Trophy Reward'],
            ['tag' => 'usereward', 'amount' => 750, 'name' => 'User Reward'],
            ['tag' => 'trophyaward', 'amount' => 2000, 'name' => 'Trophy Award'],
            ['tag' => 'bonuspayout', 'amount' => 1500, 'name' => 'Bonus Win'],
            ['tag' => 'activatedbonus', 'amount' => 100, 'name' => 'Bonus Activated'],
            ['tag' => 'transfervault', 'amount' => 5000, 'name' => 'Vault Transfer']
        ];

        echo "Starting API Event Feed Test\n";
        echo "===========================\n\n";

        // Helper function to add events for a user
        $addEventsForUser = function($user, $userType) use ($test_events, $games) {
            echo "\nAdding events for {$userType} user...\n";
            foreach($test_events as $event){
                // For game-related events, use real game data
                if(in_array($event['tag'], ['woningame', 'freespins'])) {
                    $game = $games[array_rand($games)];
                    $event['name'] = $game['game_name'];
                    $event['url'] = $game['game_id'];
                    echo "Adding {$event['tag']} event - Game: {$event['name']}\n";
                } else {
                    echo "Adding {$event['tag']} event\n";
                }
                
                uEvent($event['tag'], $event['amount'], $event['name'] ?? '', $event['url'] ?? '', $user->data);
            }
        };

        // Add events for both users
        $addEventsForUser($u_other, 'other');
        $addEventsForUser($u_to_test, 'test');
    }

    function testEventFeed($u_to_test, $u_other){
        $players = $this->db->loadArray("SELECT * FROM users ORDER BY id DESC LIMIT 100");

        foreach($players as $player){
            uEvent('openaccount', '', '', '', $player);
        }

        uEvent('openaccount', '', '', '', $u_to_test->data);

        $test_events = phive('UserHandler')->getEvents(EventsRequestData::fromArray([0, 5, $u_to_test->getId()]));
        echo "Events for {$u_to_test->getUsername()}\n";
        print_r($test_events);
        $other_events = phive('UserHandler')->getEvents(EventsRequestData::fromArray([0, 5, $u_other->getId()]));
        echo "Events for {$u_other->getUsername()}\n";
        print_r($other_events);
    }

}
