<?php

require_once 'TestGpr.php';

class TestPushgamingGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                    'bonusPlayerAwardStatuses' => [
                        [
                            'status' => 'active'
                        ]
                    ]
                ];
                break;
            case 'cancelFrb':
                 return [
                    'bonus' => [
                        'bonusId' => 123
                    ]
                ];
                break;
            default:
                break;
                
        }
    }

    public function getHeaders(){
        $headers = [
            'Operator-API-Key: BGxlWLJaRNerMW3'
        ];
        if(!empty($this->sess_key)){
            $headers[] = 'Authorization: Bearer '.$this->getToken();
        } else {
            // We mock an expired token.
            $headers[] = 'Authorization: Bearer 123abc_1';
        }
        return $headers;
    }
    
    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['token'];
        
        $arr = [
            'channel' => 'PC',
        ];
        
        return $this->_post($arr, '/rgs/hive/player/auth?rgsGameId='.$args['gid']);
    }

    public function balance($args){
        $arr = [];
        return $this->_post($arr, "/rgs/hive/player/{$this->getUsrId($args['uid'])}/wallet?rgsGameId=" .$args['gid']);
    }

    public function bet($args, $bet_id = null){
        $this->bet_id = $bet_id ?? rand(1000000, 10000000);
        $this->round_id = rand(9000000, 10000000);

        $arr = [
            'txnDeadline' => phive()->hisMod('+1 day', null, 'Y-m-d\TH:i:s\.B\Z'),
            'rgsTxnId' => $this->bet_id,
            'rgsPlayId' => $this->round_id,
            'rgsRoundId' => $this->round_id,
            'playerId' => $this->getUsrId($args['uid']),
            'playComplete' => false,
            'roundComplete' => false,
            'currency' => $args['currency'],
            'actions' => [
                [
                    'rgsActionId' => phive()->uuid(),
                    'amount' => $args['bet'],
                    'type' => 'STAKE'
                ]
            ],
            'rgsGameId' => $args['gid']
        ];

        return $this->_post($arr, '/rgs/hive/txn');
    }

    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);
        $this->round_id =  rand(1000000, 10000000);

        $arr = [
            'txnDeadline' => phive()->hisMod('+1 day', null, 'Y-m-d\TH:i:s\.B\Z'),
            'rgsTxnId' => $this->win_id,
            'rgsPlayId' => $this->round_id,
            'rgsRoundId' => $this->round_id,
            'playerId' => $this->getUsrId($args['uid']),
            'playComplete' => $last_round,
            'roundComplete' => $last_round,
            'currency' => $args['currency'],
            'actions' => [
                [
                    'rgsActionId' => phive()->uuid(),
                    'amount' => $args['win'],
                    'type' => 'WIN'
                ]
            ],
            'rgsGameId' => $args['gid']
        ];

        if(!empty($this->frb_id)){
            $arr['actions'] = [[
                'rgsActionId' => $this->frb_id,
                'amount' => $args['win'],
                'type' => 'RGS_FREEROUND_CLEARDOWN'
            ]];
        }

        return $this->_post($arr, '/rgs/hive/txn');
    }

    public function jpWin($args){
        $this->win_id = rand(1000000, 10000000);
          $arr = [
            'cashiertoken'  => $this->getToken(),
            'channel'       => 'web',
            'gameref'       => $args['gid'],
            'amount'        => 10000,
            'customerid'    => $this->getUsrId($args['uid']),
            'txid'          => $this->win_id,
            'gamesessionid' => $this->round_id,
            'ended'         => true,
            'txtype'        => 'deposit',
            "jackpotpayout" => [
                [
                    5,
                    10000
                ]
            ]
        ];

        return $this->_post($arr, 'deposit');
    }

    public function rollback($args, $origin_id){
        $arr = [
            'playerId' => $this->getUsrId($args['uid'])
        ];

        return $this->_post($arr, "/rgs/hive/txn/{$origin_id}/cancel");
    }

    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $this->balance($args);
        

        //$this->jpWin($args);
        //exit;

        $this->bet($args);
        
        //$this->rollback($args, $this->bet_id);
        //exit;
        
        // Idempotency test
        $this->bet($args, $this->bet_id);
        
        $this->win($args, null, true);
        
        // Idempotency test.
        $this->win($args, $this->win_id);
    }
    
}
