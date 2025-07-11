<?php

require_once 'TestGpr.php';

class TestNyxGpr extends TestGpr
{

    public function init($args){
        parent::init($args);
        $this->http_data_type = 'form';
        $this->echo_res_body = true;
        $this->apiversion = 1.2;
    }

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                // NOTE, the below should work for the OS API AUTH call as well.
                return [
                    'RSP' => [
                        'rc' => 0,
                        'sapisession' => 12345,
                        'promotions' => [
                            ['campaignid' => '10-promo']
                        ]
                    ]
                ];
                break;
            default:
                break;
                
        }
    }

    public function getParams($arr){
        return array_merge([
            'loginname' => 'nyxLogin',
            'password' => 'nyxPassword',
            'apiversion' => $this->apiversion
        ], $arr);
    }
    
    public function balance($args){
        $arr = $this->getParams([
            'gamesessionid' => $this->sess_key,
            'accountid'     => $this->getUsrId($args['uid']),
            'request'       => 'getbalance',
            'device'        => $args['device'],
        ]);
        
        return $this->_get($arr, '', $args);
    }

    
    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['sessionid'];

        $arr = $this->getParams([
            'sessionid' => $this->sess_key,
            'request' => 'getaccount',
        ]);
        
        return $this->_get($arr, '', $args);
    }

    public function ping($args){
        $arr = $this->getParams([
            'request' => 'ping',
        ]);
        
        return $this->_get($arr, '', $args);
    }
    
    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }

        $arr = $this->getParams([
            'transactionid' => $this->bet_id,
            'gamesessionid' => $this->sess_key,
            'accountid'     => $this->getUsrId($args['uid']),
            'request'       => 'wager',
            'betamount'     => $args['bet'],
            'roundid'       => $this->round_id,
            'nogsgameid'    => $args['gid'],
            'device'        => $args['device']
        ]);
        
        return $this->_get($arr, '', $args);        
    }

    public function frbWin($args, $frb_id){
        $this->win_id = rand(1000000, 10000000);

        $arr = $this->getParams([
            'transactionid' => $win_id ?? $this->win_id,
            'gamesessionid' => $this->sess_key,
            'accountid'     => $this->getUsrId($args['uid']),
            'request'       => 'result',
            'wonamount'     => $args['win'],
            'roundid'       => $this->round_id ?? rand(1000000, 10000000),
            'nogsgameid'    => $args['gid'],
            'device'        => $args['device'],
            'gamestatus'    => 'completed',
            'activationid'  => $frb_id
        ]);

        return $this->_get($arr, '', $args);        
    }

    public function win($args, $win_id = null, $jp_win = null){
        if(!$win_id){
            $this->win_id = rand(1000000, 10000000);
        }

        $arr = $this->getParams([
            'transactionid' => $win_id ?? $this->win_id,
            'gamesessionid' => $this->sess_key,
            'accountid'     => $this->getUsrId($args['uid']),
            'request'       => 'result',
            'result'        => $args['win'],
            'roundid'       => $this->round_id ?? rand(1000000, 10000000) ,
            'nogsgameid'    => $args['gid'],
            'device'        => $args['device'],
            'gamestatus'    => 'completed'
        ]);

        if(!empty($jp_win)){
            $arr['jpw'] = $jp_win;
        }
        
        return $this->_get($arr, '', $args);        
    }

    public function jpWin($args){
        return $this->win($args, null, 100.10);
    }

    public function rollback($args, $bet_id){
        
        $arr = $this->getParams([
            'transactionid' => $bet_id,
            'gamesessionid' => $this->sess_key,
            'accountid'     => $this->getUsrId($args['uid']),
            'request'       => 'rollback',
            'roundid'       => $this->round_id,
            'nogsgameid'    => $args['gid'],
            'device'        => $args['device']
        ]);
        
        return $this->_get($arr, '', $args);        
    }
    
    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->mobileLaunchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";        
        
        $this->authorize($args, $launch_url);
        
        $this->balance($args);
        
        $this->bet($args);
        
        //$this->rollback($args, $this->bet_id);
        //exit;

        // Idempotency test
        //$this->bet($args, $this->bet_id);

        $this->win($args, null);
        //$this->jpWin($args);
        //$this->rollback($args, $this->bet_id);
        
        // Idempotency test.
        //$this->win($args, $this->win_id);
        
    }
    
}
