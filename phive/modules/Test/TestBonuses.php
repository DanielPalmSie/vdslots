<?php
class TestBonuses extends TestPhive{

    function __construct(){
        $this->bh       = phive('Bonuses');
        $this->db       = phive('SQL');
    }
    
    function setup($u){
        $this->uid      = uid($u);
        $this->uname 	= $u->getUsername();
        $this->u        = $u;
        return $this;
    }

    /**
     * We have these scenarios:
     *
     * 1.) Same bonus id and active, this should refuse for all.
     * 2.) Same bonus id and inactive, 2 and 4 should pass this.
     * 3.) Same type and active, 2 should fail this.
     * 4.) Same type and inactive.
     *
     * @param $u_obj 
     */    
    function testExclusivity($u_obj){
        //$types = [0, 1];
        $types = [0, 1, 2, 3, 4];

        echo "\nTypes:\n";
        echo "\n Type 0: not exclusive, can not be used with active exclusives or reactivated";
        echo "\n Type 1: super exclusive, can not be reactivated, will always work but fail other active bonuses";
        echo "\n Type 2: not exclusive, can not be used with other active exclusives, can be reactivated, can not have other type 2s active";
        echo "\n Type 3: not exclusive, can be used with other active exclusives, can not be reactivated, can have other type 3s active";
        echo "\n Type 4: not exclusive, can be used with other active exclusives, can be reactivated, can have other type 4s active";
        echo "\n";
        
        echo "\nTo activate: [type]|[id] => has [type]|[id] which is [status] => [result] \n\n";
        
        foreach($types as $to_activate_type){
            $b = [
                'id' => 99,
                'exclusive' => $to_activate_type,
            ];
            foreach($types as $current_type){
                foreach(['active', 'approved'] as $current_status){
                    foreach([99, 100] as $parent_id){

                        if($parent_id == $b['id']){
                            if($to_activate_type != $current_type){
                                // We are looking at the same bonus which means that the types should be the same,
                                // different types is not possible in a real scenario.
                                continue;
                            }
                            $parent_str = 'same';
                        } else {
                            $parent_str = 'diff';
                        }
                                                
                        $current_entries = [[
                            'id'        => 10,
                            'parent_id' => $parent_id,
                            'status'    => $current_status,
                            'bonus_id'  => $parent_id,
                            'exclusive' => $current_type
                        ]];
                        
                        $res = $this->bh->handleExclusive($b, $u_obj->getId(), false, $current_entries);

                        /*
                        switch([$to_activate_type, $current_status]){
                            case [0, 'active']:
                                
                                break;
                        }
                        */
                        
                        $res_str = $res === true ? 'worked' : 'failed';
                        echo "To activate: $to_activate_type|{$b['id']} => has $current_type|$parent_id which is $current_status => $res_str \n";
                    }
                }
            }
            echo "\n";
        }
    }
    
    function doFirstDeposit($u_obj, $reset = true){
        if($reset){
            $this->reset($u_obj);
        }
        phive('Casino')->depositCash($u_obj, 10000, 'worldpay', uniqid(), 'visa', '3243 56** **** 1234', 122);
    }

    /**
     * @throws Exception
     */
    function playFrb($u_obj, $bonus_id = 2122, $num_bets = 1, $bet_amount = 100, $is_winning = true, $win_amount = 100){
        $entry = $this->db->sh($u_obj)->loadAssoc("select * from bonus_entries where bonus_id = 2122 and user_id = ".$u_obj->getId());
        $bonus = phive('Bonuses')->getBonus($bonus_id);
        $game  = phive('MicroGames')->getByGameId($bonus['game_id']);
        //print_r($game);
        $tr_id = "2122" . random_int(1000, 100000) . "0";
        $ext_id = "2122" . random_int(1000, 100000) . "0";
        for($i = 0; $i < $num_bets; $i++){
            $tr_id  = $tr_id + 1;
            $ext_id = $ext_id + 1;
            // The bet
            phive('Casino')->betHandleBonuses(ud($u_obj), $game, $bet_amount, 10000, 0, $tr_id, $ext_id);
            if($is_winning){
                // The win
                phive('Casino')->handleFspinWin($entry, $win_amount, $u_obj, 'FRB win');
            }
        }
    }
    
    // 11 welcome is 2122, and welcome deposit is 1001
    function giveWelcome($u_obj){
        $this->reset($u_obj);
        $this->addUserBonus(true, 2122, false);
    }

    function giveWelcomeAward($u_obj, $aid = 323){
        $this->reset($u_obj);
        phive('Trophy')->giveAward($aid, $u_obj->data, 500);
    }
    
    function getBalance($gref){
        return $this->bh->getBalanceByRef($gref, $this->uid);
    }
    
    function reset($u_obj){
        $this->clearTable($u_obj, ['bonus_entries', 'cash_transactions', 'actions', 'user_flags', 'trophy_award_ownership']);
    }

    function giveCommon($b, $bid, $rcode = 'bonustest'){
        $bid = empty($bid) ? rand(1, 1000000) : $bid;
        $entry = phive('SQL')->sh($this->uid)->loadAssoc("SELECT * FROM bonus_entries WHERE bonus_id = {$bid}");
        if(!empty($entry))
            return;
        
        $bonus = [
            'id'                       => $bid ,
            'expire_time'              => '2023-12-19' ,
            'num_days'                 => 7,
            'bonus_name'               => 'Videoslots - test bonus, id: '.$bid,
            'deposit_limit'            => 10000,
            'rake_percent'             => 15000,
            'deposit_multiplier'       => 0.5,
            'bonus_type'               => 'casino',
            'exclusive'                => 3,
            'type'                     => 'casino' ,
            'game_tags'                => 'slots,videoslots',
            'reload_code'              => $rcode,
            'deposit_max_bet_percent'  => 0.5,
            'bonus_max_bet_percent'    => 0.5,
            'max_bet_amount'           => 2001,
            'fail_limit'               => 100,
            'game_percents'            => 'rtp,rtp',
            'progress_type'            => 'both'
        ]; 

        foreach($b as $key => $val)
            $bonus[$key] = $val;
        
        $this->bh->setCurReload($bonus['reload_code'], $this->u);
        phive('SQL')->shs('', '', 'bonus_types')->insertArray('bonus_types', $bonus);
        phive('Casino')->depositCash($this->u, 10000, 'neteller', rand(1, 100000), '', '', rand(1, 1000000), false, 'approved', null, rand(1, 1000000), 'Neteller');        
    }
    
    function giveCashBonus($bid = '', $rcode = 'bonustest'){
        $this->giveCommon([], $bid, $rcode);
    }

    function giveCasinoWager($bid = '', $rcode = 'bonustest'){
        $this->giveCommon([
            'bonus_type'      => 'casinowager',
            'stagger_percent' => 0.1
        ], $bid, $rcode);        
    }

    // Bonus id is 11 welcome starburst.
    function addUserBonus($delete = true, $bonus_id = 2122, $activate = true){
        $ud       = $this->u->data;
        if($delete)
            $this->db->sh($ud, 'id')->query("DELETE FROM bonus_entries WHERE user_id = {$ud['id']}");
        phive('Bonuses')->addUserBonus($ud['id'], $bonus_id, $activate);
        $be       = $this->db->sh($ud, 'id')->loadAssoc("SELECT * FROM bonus_entries WHERE user_id = {$ud['id']} ORDER BY id DESC LIMIT 0,1");
        $negation = $be['user_id'] != $ud['id'] ? 'not' : '';
        echo "Bonus addition was $negation successful\n";        
    }

    // Bit rot below
    function testReloadDeposit($user, $reload_code, $amount = 10000){
        phive('SQL')->truncate('bonus_entries');
        $sql = phive('SQL');
        $sql->query("UPDATE bonus_types SET expire_time = '2100-01-01' WHERE reload_code = '$reload_code'");
        $_SESSION['reload_code'] = $reload_code;
        phive('Bonuses')->setCurReload($reload_code, $user);
        phive('QuickFire')->depositCash($user, $amount, 'dc');
        $eid = $sql->insertBigId();
        phive('Bonuses')->activatePendingEntry($eid);
    }

    function giveFrbLocal($user_id, $bonus_id, $init_status = 'active', $turnover = 0, $ext_id = null){

        $bonus = $this->bh->getBonus($bonus_id, false);
        $bonus['rake_percent'] = $turnover;
        $bonus['progress_type'] = 'both';
        phive('SQL')->save('bonus_types', $bonus);

        $user = cu($user_id);
        $this->reset($user);
        
        $inserts = [
            'bonus_id' => $bonus['id'],
            'user_id' => $user_id,
            'progress' => 0,
            'balance' => 0,
            'cost' => 0,
            'reward' => 0
        ];
        
        $inserts['frb_granted'] = $inserts['frb_remaining'] = $bonus['reward'];
        
        $start_time = date('Y-m-d');
        $inserts['start_time'] 	 = $start_time;
        $inserts['end_time'] 	 = date("Y-m-d",strtotime("+ ".$bonus['num_days']." days"));
        $inserts['activated_time'] = phive()->hisNow();
        $inserts['status'] 	 = empty($turnover) ? $init_status : 'active';
        $inserts['ext_id'] 	 = $ext_id ?? uniqid();
        
        $keys = ['game_tags', 'game_percents', 'loyalty_percent', 'bonus_tag', 'progress_type', 'allow_race', 'bonus_type'];
        foreach($keys as $key){
            $inserts[$key] = $bonus[$key];
        }

        $new_id = phive("SQL")->sh($user_id)->insertArray('bonus_entries', $inserts);

        $inserts['id'] = $new_id;        
        return $inserts;
    }
    
}
