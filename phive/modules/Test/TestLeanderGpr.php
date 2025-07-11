<?php

require_once 'TestGpr.php';

class TestLeanderGpr extends TestGpr
{

    public function init($args){
        parent::init($args);
        $this->http_data_type = 'form';
        $this->echo_res_body = true;

    }

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                    'data' => [
                        'promotion_definition_id' => 123
                    ]
                ];
                break;
            case 'cancelFrb':
                return 'ok';
                break;
            default:
                break;
                
        }
    }

    public function balance($args){
        $arr = [
            'token' => $this->sess_key,
            'userId' => $this->getUsrId($args['uid']),
            'gameId'    => $args['gid']
        ];
        
        return $this->_get($arr, 'initializeGame', $args);
    }

    
    public function authorize($args){
        $arr = [
            'token' => $this->sess_key,
            'userId' => $this->getUsrId($args['uid']),
            'gameId'    => $args['gid']
        ];
        
        return $this->_get($arr, 'requestBalance', $args);
    }

    public function endSession($args){
        $arr = [
            'token'  => $this->sess_key,
            'userId' => $this->getUsrId($args['uid']),
            'gameId' => $args['gid']
        ];
        
        return $this->_get($arr, 'finalizeGame', $args);
    }
    
    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }
        
        $arr = [
            'token'          => $this->sess_key,
            'userId'         => $this->getUsrId($args['uid']),
            'amountCurrency' => $args['currency'],
            'amount'         => $args['bet'],
            'playId'         => $this->round_id,
            'transactionId'  => $bet_id ?? $this->bet_id,
            'gameId'         => $args['gid'],
            'operation'      => 'DEBIT'
        ];

        if(!empty($this->frb_id)){
            $arr['playingFreePlay'] = 'True';
            $arr['promotionCode'] = $this->frb_id;
        }

        return $this->_get($arr, 'updateBalance', $args);
    }

    public function win($args, $win_id = null){
        if(!$win_id){
            $this->win_id = rand(1000000, 10000000);
        }
        
        $arr = [
            'token'          => $this->sess_key,
            'userId'         => $this->getUsrId($args['uid']),
            'amountCurrency' => $args['currency'],
            'amount'         => $args['win'],
            'playId'         => $this->round_id,
            'transactionId'  => $win_id ?? $this->win_id,
            'gameId'         => $args['gid'],
            'operation'      => 'CREDIT',
            'type'           => 'REGULAR'
        ];

        if(!empty($this->frb_id)){
            $arr['promotionCode'] = $this->frb_id;
            if($this->frb_tot_cnt == $this->frb_cnt){
                $arr['promotionFinished'] = 'True';
            }

            if(!empty($arr['amount'])){
                $arr['playingFreePlay'] = 'True';
            } else {
                // Zero win so more informative, therefore no tr id.
                unset($arr['transactionId']);
                unset($arr['operation']);
                unset($arr['type']);
            }
            
        }
        
        return $this->_get($arr, 'updateBalance', $args);
    }

    public function jpWin($args, $win_id = null){
        if(!$win_id){
            $this->win_id = rand(1000000, 10000000);
        }
        
        $arr = [
            'token'          => $this->sess_key,
            'userId'         => $this->getUsrId($args['uid']),
            'amountCurrency' => $args['currency'],
            'amount'         => $args['win'],
            'playId'         => $this->round_id,
            'transactionId'  => $win_id ?? $this->win_id,
            'gameId'         => $args['gid'],
            'operation'      => 'CREDIT',
            'type'           => 'JACKPOT'
        ];

        return $this->_get($arr, 'updateBalance', $args);
    }

    public function rollback($args, $bet_id){
        $arr = [
            'token'          => $this->sess_key,
            'userId'         => $this->getUsrId($args['uid']),
            'amountCurrency' => $args['currency'],
            'playId'         => $this->round_id,
            'transactionId'  => $bet_id,
            'gameId'         => $args['gid'],
        ];

        return $this->_get($arr, 'voidTransaction', $args);
    }
    
    public function launchUrl($args){
        $url = parent::launchUrl($args);
        $params = $this->urlParseVars($url);
        $this->sess_key = $params['token'];
        return $url;
    }
    
    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        $this->authorize($args);
        
        $this->balance($args);
        $this->bet($args);
        exit;
        
        //$this->rollback($args, $this->bet_id);
        //exit;

        // Idempotency test
        $this->bet($args, $this->bet_id);

        $this->win($args, null);
        //exit;
        
        // Idempotency test.
        $this->win($args, $this->win_id);
        $this->endSession($args);        
    }
    
}
