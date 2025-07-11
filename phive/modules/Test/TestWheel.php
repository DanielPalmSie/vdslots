<?php
class TestWheel extends TestPhive{
    
    function __construct(){
        $this->db = $sql = phive('SQL');
        $this->th = phive('Trophy');
        $this->wh = phive('DBUserHandler/JpWheel');
    }

    function incrJpValue($time){
        $u = cu('devtestse');
        while(true){
            if($time < time()){
                //usleep(rand(1, 10));
                phive('Trophy')->giveJackpotContribution(['payout_percent' => 0.96], $u, 100);
                // $this->db->incrValue('jackpots', 'amount', array('id' => 1), 10);
                exit;
            }
        }
    }
    
    function simultaneousJpIncrease($num = 5){
        foreach(range(1, $num) as $i){
            phive()->pexec('Test:Wheel', 'incrJpValue', [time() + 5]);
        }
    }
    
    function resetJpCache(){
        $this->db->delete('misc_cache', ['id_str' => 'jp-values']);
        $arr = [];
        $jps = [
            'MEGA_JACKPOT' => '5000000.000000000000',
            'MAJOR_JACKPOT' => '2500000.000000000000',
            'MINI_JACKPOT' => '10000.000000000000'
        ];
        foreach($jps as $alias => $prev_amount){
            $cur_amount = $this->db->getValue("SELECT amount FROM jackpots WHERE jpalias = '$alias'");
            $arr[$alias]['curr_amount'] = $cur_amount;
            $arr[$alias]['prev_amount'] = $prev_amount;
        }

        print_r($arr);
        
        phive()->miscCache('jp-values', json_encode($arr), true);
    }
    
    function resetJps(){
        $map = [
            'MEGA_JACKPOT' => 500000.000000000000,
            'MAJOR_JACKPOT' => 250000.000000000000,
            'MINI_JACKPOT' => 50000.000000000000
        ];

        foreach($this->db->loadArray("SELECT * FROM jackpots", 'ASSOC', 'jpalias') as $alias => $jp){
            $amount = $map[$alias];
            $jp['amount'] = $amount;
            $jp['amount_minimum'] = $amount;
            $this->db->save('jackpots', $jp);
        }
    }

    function simulateJpPlayContrib(){
        phive('SQL')->loopShardsSynced(function($db, $shard, $id){

            echo "$id\n";
            
            $rows = $db->loadArray("SELECT * FROM bets_tmp WHERE game_ref LIKE 'netent_%'");

            foreach($rows as &$r){
                $u_obj = cu($r['user_id']);
                $game = phive("MicroGames")->getByGameRef($r['game_ref']);
                phive('Trophy')->giveJackpotContribution($game, $u_obj, $r['amount']);
            }            
        });
    }
    
    function simulateJpContrib($u_obj, $bet, $rounds){
        $this->resetJps();
        foreach(range(0, $rounds) as $i){
            $this->testJpContrib($u_obj, $bet, false);
            echo "$i\n";
        }
    }
    
    function testJpContrib($u_obj, $bet = 10000, $echo = true, $game = null){
        // $this->resetJps();
        $game = $game ?? ['payout_percent' => 0.96];
        phive('Trophy')->giveJackpotContribution($game, $u_obj, $bet);
        if($echo){
            echo "\nAfter a $bet cents bet in a game with {$game['payout_percent']} RTP:  \n";
            foreach($this->db->loadArray("SELECT * FROM jackpots") as $jp){
                echo "{$jp['jpalias']}: {$jp['amount']}\n";
            }
        }
    }
    
    function generateWheels(){
        $wheels = $this->db->loadArray("SELECT * FROM jackpot_wheels");
        foreach($wheels as $wheel){
            if(empty($wheel['active'])){
                echo "Wheel {$wheel['id']} is not active \n";
            } else {
                echo "Wheel {$wheel['name']}: \n";
                $slices = $this->wh->generateWheel($wheel['id']);
                foreach($slices as $s){
                    echo $s['award']['description']."\n";
                }
                //print_r($slices);
                echo "\n\n";
            }
        }
    }
    
    function testJpIncrease(){
        $this->wh->updateJpValues();
        foreach($this->db->loadArray("SELECT * FROM jackpots") as $jp){
            $jp['amount'] += 100;
            $this->db->save('jackpots', $jp);
        }
        $this->wh->updateJpValues();
        $res = $this->wh->getCache();
        print_r($res);
    }
    
    function prWojLog(){
        $res = $this->db->shs('')->loadArray('SELECT * FROM jackpot_wheel_log');
        print_r($res);
    }
    
    function fixSliceOrder(){
        $wheels = $this->db->loadArray("SELECT * FROM jackpot_wheels");
        foreach($wheels as $wheel){
            $i = 0;
            $slices = $this->db->loadArray("SELECT * FROM jackpot_wheel_slices WHERE wheel_id = ".$wheel['id']);
            foreach($slices as $slice){
                $slice['sort_order'] = $i;
                $this->db->save('jackpot_wheel_slices', $slice);
                $i++;
            }
        }
    }
    
    function insertSlice($wheel_id, $awards, $probability){
        $award_ids = implode(',', array_column($awards, 'id'));
        $ins = [
            'wheel_id'    => $wheel_id,
            'award_id'    => $award_ids,
            'probability' => $probability
        ];

        return $this->db->insertArray('jackpot_wheel_slices', $ins);
    }

    function insertAward($wheel){

        $alias = 'wheel_of_jackpots_'.$wheel['id'];
        
        $this->db->insertArray('trophy_awards', [
            'type'        => 'wheel-of-jackpots',
            'description' => "Wheel of JPs #{$wheel['id']}",
            'alias'       => $alias,
            'mobile_show' => 1,
            'jackpots_id' => empty($wheel['country']) ? $wheel['id'] : 0
        ]);
    }
    
    function setup($u_obj){
        $this->db->truncate('jackpot_wheels', 'jackpot_wheel_slices');

        $country = $u_obj->getCountry();
        
        $wheel1 = [
            'name'             => "Default $country wheel",
            'number_of_slices' => 13,
            'cost_per_spin'    => 100,
            'active'           => 1,
            'country'          => $country
        ];

        $wheel2 = [
            'name'             => "A VIP wheel",
            'number_of_slices' => 4,
            'cost_per_spin'    => 999,
            'active'           => 1
        ];

        $wheel3 = [
            'name'             => 'The default ALL wheel',
            'number_of_slices' => 13,
            'cost_per_spin'    => 100,
            'active'           => 1,
            'country'          => 'ALL'
        ];

        $wheel4 = [
            'name'             => 'The no win wheel',
            'number_of_slices' => 10,
            'cost_per_spin'    => 100,
            'active'           => 1
        ];

        
        $wheel_id1 = $this->db->insertArray('jackpot_wheels', $wheel1);
        $wheel_id2 = $this->db->insertArray('jackpot_wheels', $wheel2);
        $wheel_id3 = $this->db->insertArray('jackpot_wheels', $wheel3);
        $wheel_id4 = $this->db->insertArray('jackpot_wheels', $wheel4);

        $wheel1['id'] = $wheel_id1;
        $wheel2['id'] = $wheel_id2;
        $wheel3['id'] = $wheel_id3;
        $wheel4['id'] = $wheel_id4;
        
        $jp_awards = $this->th->getAwardsByType('jackpot');
        $awards    = $this->db->loadArray("SELECT * FROM trophy_awards");
        shuffle($awards);
        
        foreach($jp_awards as $jp_award){
            $this->insertSlice($wheel_id1, [$jp_award], 10);
            $this->insertSlice($wheel_id3, [$jp_award], 10);
            $this->insertSlice($wheel_id2, [$jp_award], 2500000);
        }

        foreach(range(1, $wheel1['number_of_slices'] - count($jp_awards)) as $num){
            $this->insertSlice($wheel_id1, [array_pop($awards), array_pop($awards)], 999997);
            $this->insertSlice($wheel_id3, [array_pop($awards), array_pop($awards)], 999997);
        }

        $this->insertSlice($wheel_id2, [array_pop($awards), array_pop($awards)], 2500000);

        foreach(range(1, $wheel4['number_of_slices']) as $num){
            $this->insertSlice($wheel_id4, [], 1000000);
        }
        
        $this->db->delete('trophy_awards', ['type' => 'wheel-of-jackpots']);

        
        // No need to insert wheel 1 too as we only need one dummy award for the country logic to work
        $this->insertAward($wheel3);
        
        $this->insertAward($wheel2);
        
        $this->insertAward($wheel4);

        $this->fixSliceOrder();
    }

    function testRandomization($u_obj){
        $awards = $this->th->getAwardsByType('wheel-of-jackpots');
        foreach($awards as $award){
            $this->clearTable($u_obj, ['trophy_award_ownership']);
            $this->th->giveAward($award, ud($u_obj));
            $this->th->useAward($award['id'], $u_obj->getId());            
            $cur_wheel = $this->wh->displayWheel($u_obj, '#fde19b', '#bf9c46');
            echo "Current wheel for display:\n";
            print_r(array_column($cur_wheel, 'text'));

            phMdelShard('cur-wheel-'.$award['jackpots_id'], $u_obj);
            
            $this->clearTable($u_obj, ['trophy_award_ownership']);
            $this->th->giveAward($award, ud($u_obj));
            $this->th->useAward($award['id'], $u_obj->getId());
            $cur_wheel = $this->wh->displayWheel($u_obj, '#fde19b', '#bf9c46');
            echo "Current wheel for display, should look differently from the first print out if there are randoms:\n";
            print_r(array_column($cur_wheel, 'text'));
            
            phMdelShard('cur-wheel-'.$award['jackpots_id'], $u_obj);
        }  
    }
    
    function testFlow($u_obj){
        $awards = $this->th->getAwardsByType('wheel-of-jackpots');

        $this->clearPlayer($u_obj);
        
        foreach($awards as $award){
            $this->th->giveAward($award, ud($u_obj));
            
            $this->th->useAward($award['id'], $u_obj->getId());
            $cur_wheel = $this->wh->displayWheel($u_obj, '#fde19b', '#bf9c46');
            echo "Current wheel for display:\n";
            print_r($cur_wheel);

            $cur_wheel = $this->wh->displayWheel($u_obj, '#fde19b', '#bf9c46');
            echo "Current wheel for display, should look exactly the same as the first print out:\n";
            print_r($cur_wheel);

            $this->wh->spin($u_obj);

            echo "Wheel log:\n";
            $this->printTable($u_obj, 'jackpot_wheel_log');

            echo "Award ownership:\n";
            $this->printTable($u_obj, 'trophy_award_ownership');

            $cur_wheel = phM('asArr', 'cur-wheel-*');
            echo "Current wheel, should be empty:\n";
            print_r($cur_wheel);

        }
    }

    function clearPlayer($u_obj, $do_log = true){
        $tables = ['trophy_award_ownership'];
        if($do_log){
            $tables[] = 'jackpot_wheel_log';
        }
        $this->clearTable($u_obj, $tables);
    }

    function clearAwards(){
        $this->db->delete('trophy_awards', ['type' => 'wheel-of-jackpots']);
    }
    
    function giveAwards($u_obj){
        $awards = $this->th->getAwardsByType('wheel-of-jackpots');

        print_r($awards);
        
        foreach(range(1, 1) as $num){
            foreach($awards as $award){
                $this->th->giveAward($award, ud($u_obj));
            }
        }
    }

    /**
     * Returns the odd to get a wheel given a setting
     *
     * @param int $odd This is 10000 if desired probability is 1:100, so having one wheel each 100 spins.
     *
     */
    public function getWheelOnBetProbabilitySetting($prob, $spins = 10000000)
    {
        $award = 0;
        for($i=0;$i < $spins; $i++) {
            if(mt_rand(1, 1000000) <= $prob){
                $award++;
            }
        }
        dd2($award, $spins/$award);
    }
}
