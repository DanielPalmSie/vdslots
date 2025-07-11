<?php

require_once 'TestGpr.php';

class TestPlaytechGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                    'requestId' => uniqid(),
                    'bonusInstanceCode' => 12345
                ];
                break;
            case 'cancelFrb':
                return [
                    'requestId' => uniqid()
                ];
                break;
            default:
                break;
                
        }
    }

    // {"request":[],"body":"{\"requestId\":\"21371b13-cacb-4c8c-af63-fdbffcc5dea6\",\"username\":\"VIDEOS__5229088_1\",\"remoteBonusCode\":\"219_1\",\"bonusInstanceCode\":\"5105924\",\"resultingStatus\":\"ACCEPTED\",\"date\":\"2023-10-23 06:24:28.000\",\"freeSpinsChange\":10,\"bonusTemplateId\":\"44166\",\"freeSpinValue\":\"0\"}"}
    public function notifybonusevent($args){
        $arr = [
            'requestId'     => uniqid()
        ];
        
        return $this->_post($arr, 'notifybonusevent');
    }

    
    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['externalToken'];
        
        $arr = [
            'externalToken' => $this->sess_key,
            'requestId'     => uniqid()
        ];
        
        return $this->_post($arr, 'authenticate');
    }

    public function refreshtoken($args){
        $arr = [
            'externalToken' => $this->sess_key,
            'requestId'     => uniqid()
        ];
        return $this->_post($arr, 'keepalive');
    }

    public function endsession($args){
        $arr = [
            'externalToken' => $this->sess_key,
            'requestId'     => uniqid()
        ];
        return $this->_post($arr, 'logout');
    }

    public function balance($args){
        $arr = [
            'externalToken' => $this->sess_key,
            'requestId'     => uniqid()
        ];
        return $this->_post($arr, 'getbalance');
    }

    public function bet($args, $bet_id = null){
        $this->bet_id = $bet_id ?? rand(1000000, 10000000);
        $this->round_id = rand(1000000, 10000000);
        $arr = [
            'externalToken'   => $this->sess_key,
            'requestId'       => uniqid(),
            'username'        => $this->getUsrId($args['uid']),
            'gameCodeName'    => $args['gid'],
            'amount'          => $args['bet'],
            'transactionCode' => $this->bet_id,
            'gameRoundCode'   => $this->round_id
        ];

         if(!empty($this->frb_id)){
             $arr['bonusChanges'] = [['remoteBonusCode' => $this->frb_id]];
         }
        
        return $this->_post($arr, 'bet');
    }
    
    public function endRound($args){
        $arr = [
            'externalToken'   => $this->sess_key,
            'requestId'       => uniqid(),
            'username'        => $this->getUsrId($args['uid']),
            'gameCodeName'    => $args['gid'],
            'gameRoundCode'   => $this->round_id,
            'gameRoundClose'  => ['date' => date('Y-m-d H:i:s.m')]
        ];

        return $this->_post($arr, 'gameroundresult');
    }

    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);
        $arr = [
            'externalToken'   => $this->sess_key,
            'requestId'       => uniqid(),
            'username'        => $this->getUsrId($args['uid']),
            'gameCodeName'    => $args['gid'],
            'gameRoundCode'   => $this->round_id
        ];

        if(!empty($this->frb_id)){
            $arr['bonusChanges'] = [['remoteBonusCode' => $this->frb_id]];
        }
        
        $arr['pay'] = [
            'amount'          => $args['win'],
            'transactionCode' => $this->win_id,
            'type' => 'WIN'
        ];
        
        return $this->_post($arr, 'gameroundresult');
    }

    public function jpWin($args){
        $win_id = rand(1000000, 10000000);
        $action = 'gameroundresult';
        $arr = [
            'externalToken'   => $this->sess_key,
            'requestId'       => uniqid(),
            'username'        => $this->getUsrId($args['uid']),
            'gameCodeName'    => $args['gid'],
            'amount'          => $args['bet'],
            'gameRoundCode'   => $this->round_id,
            'jackpot' => [
                'winAmount' => 100.05
            ]
        ];

        $arr['pay'] = [
            'transactionCode' => $win_id,
            'type' => 'WIN',
            'amount'          => 100.15
        ];
        
        return $this->_post($arr, $action);
    }
    
    public function rollback($args, $origin_id){
        $arr = [
            'externalToken'   => $this->sess_key,
            'requestId'       => uniqid(),
            'username'        => $this->getUsrId($args['uid']),
            'gameCodeName'    => $args['gid'],
            'amount'          => $args['bet'],
            'gameRoundCode'   => $this->round_id
        ];

        $arr['pay'] = [
            'transactionCode' => $this->win_id,
            'relatedTransactionCode' => $origin_id,
            'type' => 'REFUND'
        ];
        
        return $this->_post($arr, 'gameroundresult');
    }

    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);

        $this->notifybonusevent($args);
        // $this->refreshtoken($args);
        // $this->endsession($args);
        
        $this->balance($args);

        //$this->jpWin($args);
        //exit;

        $this->bet($args);
        //$this->endRound($args);
        // Idempotency test
        $this->bet($args, $this->bet_id);
        
        $this->win($args, null, true);
        // Idempotency test.
        $this->win($args, $this->win_id);
        
        $this->rollback($args, $origin_id ?? $this->bet_id);
        $this->rollback($args, $origin_id ?? $this->win_id);
    }
    
}
