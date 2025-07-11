<?php

require_once 'TestPhive.php';

class TestGpr extends TestPhive
{
    public function init($args){
        $this->url = $args['url'];
        $this->brand_id = 1;
        if(!empty($args['frb_id'])){
            $this->setFrbId($args['frb_id']);
        }
        $this->http_data_type = 'json';
        $this->echo_res_body = false;
    }

    public function truncateBetsWins(){
        $this->db->truncate('bets', 'wins', 'rounds');
    }
    
    public function getUsrId($uid){
        if(!empty($this->bos_entry_id)){
            $uid .= 'e'.$this->bos_entry_id;
        }
        return $uid.'_'.$this->brand_id;
    }

    public function getToken($args = [], $token = null){
        $jur = $args['jur'] ?? 'MT';
        return ($token ?? $this->sess_key).'_'.$this->brand_id.'_'.$jur;
    }

    public function getHeaders(){
        return '';
    }

    public function setFrbId($entry_id){
        $this->frb_id = $entry_id.'_'.$this->brand_id;
    }
    
    public function _post($params, $action, $args = []){
        $url = $this->url.$action.$args['wallet_url_postfix'];
        echo "Calling $url with: \n\n";
        print_r($params);
        if($this->http_data_type == 'form'){
            $params = http_build_query($params);
            $content_type = 'application/x-www-form-urlencoded';
        } else if($this->http_data_type == 'xml') {
            $content_type = 'text/xml';
        } else {
            $content_type = 'application/json';
        }
        $res = phive()->post($url, $params, $content_type, $this->getHeaders(), 'gpr-test-out', 'POST', '', [], 'UTF-8', true);

        list($res_body, $res_code, $res_headers) = $res;
        if($this->echo_res_body){
            echo $res_body;
        }
        echo "\n{$p_arr['action']} Result Code: {$res_headers['http_code']}";
        echo "\n{$p_arr['action']} Result Body:\n";
        if($this->http_response_data_type == 'xml'){
            echo $res_body;
        } else {
            $res = json_decode($res_body, true);
            print_r($res);
        }
        echo "\n\n";
        return $res;
    }

    public function _get($params, $action, $args = []){
        $url = $this->url.$action.$args['wallet_url_postfix'];
        $params = http_build_query($params);
        $url = $url.'?'.$params;
        echo "Calling $url \n\n";
        $res = phive()->post($url, '', '', $this->getHeaders(), 'gpr-test-out', 'GET', '', [], 'UTF-8', true);
        list($res_body, $res_code, $res_headers) = $res;
        if($this->echo_res_body){
            echo "\n{$p_arr['action']} Result Body:\n $res_body \n";
        }
        echo "\n{$p_arr['action']} Result Code: {$res_headers['http_code']}";
        echo "\n\n";
        return $res;
    }

    
    public function initBos($args, $extra_players = []){
        // We need to turn OFF event queues when testing as few test / local envs have them setup.
        phive('Config')->setValue('event-queues', 'enabled', 'no');
        $t = $this->db->loadAssoc("SELECT * FROM tournaments WHERE id = 4267948");
        $t['status'] = 'in.progress';
        $now = time();
        $start_time = $now - (60 * 30);
        $t['mtt_start'] = $start_time;
        $t['start_time'] = phive()->hisNow($start_time);
        $t['end_time'] = '0000-00-00 00:00:00';
        $t['calc_prize_stamp'] = '0000-00-00 00:00:00';
        $t['included_countries'] = '';
        $t['blocked_provinces'] = '';
        $t['max_bet'] = 20;
        $t['min_bet'] = 20;
        $t['cost'] = 600;
        $t['total_cost'] = 600;
        $t['tournament_name'] = $args['brand_gid'].' TEST';
        $t['pwd'] = '';
        $t['game_ref'] = $args['brand_gid'];
        $this->db->save('tournaments', $t);
        $this->db->truncate('tournament_entries');
        $this->bos_entry_id = phive('Tournament')->insertEntry($args['u_obj'], $t);
        foreach($extra_players as $uname){
            $u_obj = cu($uname);
            phive('Tournament')->insertEntry($u_obj, $t);
        }
        $res = phive('Tournament')->getListingAdvanced([], [
            'start_format' => 'all',
            'category' => 'all',
            'status' => 'all',
        ]);
        // print_r($res);
    }

    public function injectGpr($gpr){
        $gpr->init();
        $this->gpr = $gpr;
        return $this;
    }

    public function awardFrbonus($args, $bonus_id){
        $this->clearTable($args['u_obj'], 'bonus_entries');
        // This line will call Gpr::awardFRBonus()
        $entry_id = phive('Bonuses')->addUserBonus($args['uid'], $bonus_id, true, false, phive()->today());
        return $entry_id;
    }
    
    public function cancelFrbonus($args, $entry_id){
        return $this->gpr->cancelFRBonus($args['uid'], $entry_id);
    }

    public function mobileLaunchUrl($args){
        $_SESSION['user_id'] = $args['uid'];
        if(!empty($this->bos_entry_id)){
            $_SESSION['token_uid'] = $args['uid'].'e'.$this->bos_entry_id;
        }
        $url = $this->gpr->getMobilePlayUrl($args['brand_gid'], $args['lang'], '', []);
        return $url;
    }
    
    public function launchUrl($args){
        $_SESSION['user_id'] = $args['uid'];
        if(!empty($this->bos_entry_id)){
            $_SESSION['token_uid'] = $args['uid'].'e'.$this->bos_entry_id;
        }
        $url = $this->gpr->getDepUrl($args['brand_launch_gid'] ?? $args['brand_gid'], $args['lang']);
        return $url;
    }
    
    public function doFrbRun($args, $type, $num_spins = 10, $send_zero_wins = false){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        if(method_exists($this, 'authorize')){
            $this->authorize($args, $launch_url);
        }
        $this->balance($args);

        $this->frb_tot_cnt = $num_spins;
        $this->frb_cnt = null;

        $zero_win_args = $args;
        unset($zero_win_args['win']);

        if($type == 'one_bet_one_win'){
            $this->frb_cnt = 1;
            $this->bet($args);
            $this->win($args, null, true);
        }
        
        if($type == 'no_wins'){
            foreach(range(1, $num_spins) as $i){
                $this->frb_cnt = $i;
                $this->bet($args);
                if($send_zero_wins){
                    $this->win($zero_win_args, null, true);
                }
            }
        }

        if($type == 'some_wins'){
            foreach(range(1, $num_spins) as $i){
                $this->frb_cnt = $i;
                $this->bet($args);
                if($i == 1 || $i == 7){
                    $this->win($args, null, true);
                } else if($send_zero_wins){
                    $this->win($zero_win_args, null, true);
                }
            }
        }

        if($type == 'win_on_last_bet'){
            foreach(range(1, $num_spins) as $i){
                $this->frb_cnt = $i;
                $this->bet($args);

                if($i == 5 || $i == 7){
                    $this->win($args, null, true);
                } else if($i == $num_spins){
                    $this->win($args, null, true);
                } else if($send_zero_wins){
                    $this->win($zero_win_args, null, true);
                }
            }
        }
        
        if($type == 'zero_win_on_last_bet'){
            foreach(range(1, $num_spins) as $i){
                $this->frb_cnt = $i;
                $this->bet($args);
                if($i == 5 || $i == 7){
                    $this->win($args, null, true);
                }else if($i == $num_spins){
                    $this->win($zero_win_args, null, true);
                } else if($send_zero_wins){
                    $this->win($zero_win_args, null, true);
                }
            }
        }
    }
}
