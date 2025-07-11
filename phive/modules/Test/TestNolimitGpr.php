<?php

require_once 'TestGpr.php';

class TestNolimitGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                    "jsonrpc" => "2.0",
                    "result" => (object)[],
                    "id" => phive()->uuid()
                ];
                break;
            case 'cancelFrb':
                 return [
                    "jsonrpc" => "2.0",
                    "result" => (object)[],
                    "id" => phive()->uuid()
                ];
                break;
            default:
                break;
                
        }
    }

    
    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['token'];
        
        $arr = [
            'method' => 'wallet.validate-token',
            'params' => [
                'token' => $this->sess_key,
                'game' => $args['gid']
            ],
            'id' => uniqid()
        ];
        
        return $this->_post($arr, '');
    }

    public function refresh($args){
        $arr = [
            'method' => 'wallet.keep-alive',
            'params' => [
                'extId1s' => $this->getUsrId($args['uid'])
            ],
            'id' => uniqid()
        ];
        
        return $this->_post($arr, '');        
    }

    public function balance($args){
        $arr = [
            'method' => 'wallet.balance',
            'params' => [
                'extId1' => $this->sess_key,
                'userId' => $this->getUsrId($args['uid'])
            ],
            'id' => uniqid()
        ];
        
        return $this->_post($arr, '');        
    }

    public function bet($args, $bet_id = null){
        $this->bet_id = $bet_id ?? rand(1000000, 10000000);
        $this->round_id = rand(1000000, 10000000);

        $arr = [
            'method' => 'wallet.withdraw',
            'params' => [
                'withdraw' => [
                    'amount' => $args['bet'],
                    'currency' => $args['currency']
                ],
                'information' => [
                    "uniqueReference" => $this->bet_id,
                    "gameRoundId" => $this->round_id,
                    'game' => $args['gid']
                ],
                'extId1' => $this->sess_key,
                'userId' => $this->getUsrId($args['uid'])
            ],
            'id' => uniqid()
        ];
        
        if(!empty($this->frb_id)){
            $arr['params']['promoName'] = $this->frb_id;
        }

        return $this->_post($arr, '');        
    }
    
    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $arr = [
            'method' => 'wallet.deposit',
            'params' => [
                'deposit' => [
                    'amount' => $args['bet'],
                    'currency' => $args['currency']
                ],
                'information' => [
                    "uniqueReference" => $this->win_id,
                    "gameRoundId" => $this->round_id,
                    'game' => $args['gid']
                ],
                'extId1' => $this->sess_key,
                'userId' => $this->getUsrId($args['uid'])
            ],
            'id' => uniqid()
        ];

        if(!empty($this->frb_id)){
            $arr['params']['promoName'] = $this->frb_id;
        }
        
        return $this->_post($arr, '');        
    }

    public function rollback($args, $origin_id){
        
        $arr = [
            'method' => 'wallet.rollback',
            'params' => [
                'deposit' => [
                    'amount' => $args['bet'],
                    'currency' => $args['currency']
                ],
                'information' => [
                    "uniqueReference" => $origin_id,
                    "gameRoundId" => $this->round_id,
                    'game' => $args['gid']
                ],
                'extId1' => $this->sess_key,
                'userId' => $this->getUsrId($args['uid'])
            ],
            'id' => uniqid()
        ];
        
        return $this->_post($arr, '');        
    }

    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        
        $this->authorize($args, $launch_url);
        
        // $this->refreshtoken($args);
        
        //$this->refresh($args);
        
        exit;
        $this->balance($args);
        
        //$this->jpWin($args);
        //exit;

        $this->bet($args);
        $this->rollback($args, $this->bet_id);
        exit;
        //$this->endRound($args);
        // Idempotency test
        //$this->bet($args, $this->bet_id);
        
        $this->win($args, null, true);
        // Idempotency test.
        $this->win($args, $this->win_id);
        
        
    }
    
}
