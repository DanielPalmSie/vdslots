<?php

require_once 'TestGpr.php';

class TestStakelogicGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                    'VendorBonusId' => uniqid()
                ];
                break;
            case 'cancelFrb':
                return '';
                break;
            default:
                break;
        }
    }

    
    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['token'];
        
        $arr = [
            'token' => $this->sess_key,
        ];
        
        return $this->_post($arr, 'auth');
    }

    public function insertLiveGames(){
        foreach(['live' => 'Stakelogic Live Lobby', 'LC2200' => 'Auto Roulette', 'LC3104' => 'BJ Cool Grey'] as $gid => $name){
            $insert = [
                'game_name' => $name,
                'tag' => 'live',
                'sub_tag' => 'live',
                'game_id' => "stakelogic_".$gid,
                'languages' => 'de,en,es,fi,hi,it,ja,no,sv',
                'ext_game_name' => "stakelogic_".$gid,
                'device_type' => 'flash',
                'operator' => 'Stakelogic',
                'network' => 'stakelogic',
                'payout_percent' => '0.99',
                'min_bet' => 100,
                'max_bet' => 10000,
                'width' => 1600,
                'height' => 900,
                'enabled' => 1,
                'volatility' => 5,
                'game_url' => strtolower(str_replace(' ', '-', $name)),
                'meta_descr' => '#game.meta.descr.'.strtolower(str_replace(' ', '-', $name)),
                'html_title' => '#game.meta.title.'.strtolower(str_replace(' ', '-', $name)),
            ];
            phive('SQL')->save('micro_games', $insert);
        }
    }
    
    public function balance($args){
        $arr = [
            'token' => $this->sess_key,
        ];
        return $this->_post($arr, 'balance');
    }

    public function bet($args, $bet_id = null){
        $this->bet_id = $bet_id ?? rand(1000000, 10000000);
        $this->round_id = rand(1000000, 10000000);
        $arr = [
            'token' => $this->sess_key,
            'playerId' => $this->getUsrId($args['uid']),
            'sessionId' => 'abc',
            'gameId' => $args['gid'],
            'amount' => $args['bet'],
            'currency' => $args['currency'],
            'id' => $this->bet_id,
            'roundId' => $this->round_id
        ];

        if(!empty($this->frb_id)){
            $arr['bonusId'] = $this->frb_id;
        }
        
        return $this->_post($arr, 'withdraw');
    }

    public function jpWin($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);
        $arr = [
            'token' => $this->sess_key,
            'playerId' => $this->getUsrId($args['uid']),
            'sessionId' => 'abc',
            'gameId' => $args['gid'],
            'amount' => $args['win'],
            'currency' => $args['currency'],
            'id' => $this->win_id,
            'withdrawTransactionId' => $this->bet_id,
            'lastRound' => $last_round,
            'roundId' => $this->round_id ?? rand(1000000, 10000000),
            'jackpots' => [
                [
                    'win' => 20000,
                ],
                [
                    'win' => 30000,
                ],
            ]
        ];

        if(!empty($this->frb_id)){
            $arr['bonusId'] = $this->frb_id;
        }
        
        return $this->_post($arr, 'deposit');
    }  
    
    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);
        $arr = [
            'token' => $this->sess_key,
            'playerId' => $this->getUsrId($args['uid']),
            'sessionId' => 'abc',
            'gameId' => $args['gid'],
            'amount' => $args['win'],
            'currency' => $args['currency'],
            'id' => $this->win_id,
            'withdrawTransactionId' => $this->bet_id,
            'lastRound' => $last_round,
            'roundId' => $this->round_id ?? rand(1000000, 10000000)
        ];

        if(!empty($this->frb_id)){
            $arr['bonusId'] = $this->frb_id;
        }
        
        return $this->_post($arr, 'deposit');
    }
    
    public function rollback($args, $origin_id = null){
        $origin_id = $origin_id ?? $this->bet_id;
        $arr = [
            'playerId' => $this->getUsrId($args['uid']),
            'gameId' => $args['gid'],
            'currency' => $args['currency'],
            'id' => rand(1000000, 10000000),
            'originId' => $origin_id,
            'roundId' => $this->round_id
        ];
        return $this->_post($arr, 'cancel');
    }

    public function doFullRun($args, $origin_id = null){
        $launch_url = $this->doLaunch($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);

        
        $this->balance($args);

        //$this->jpWin($args);
        //exit;

        //phive('SQL')->sh($args['u_obj'])->updateArray('ext_game_participations', ['balance' => 5], ['user_id' => $args['uid']]);
        
        $this->bet($args);
        exit;
        
        $this->rollback($args, $origin_id ?? $this->bet_id);

        exit;
        // Idempotency test
        //$this->bet($args, $this->bet_id);
        
        $this->win($args, null, true);
        // Idempotency test.
        //$this->win($args, $this->win_id);
        
    }
    
}
