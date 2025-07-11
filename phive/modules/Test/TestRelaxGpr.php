<?php

require_once 'TestGpr.php';

class TestRelaxGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                    'status' => 'ok',
                    'txid' => uniqid(),
                    'freespinids' => [
                        [
                            123, // user id
                            456 // Relax FRB id
                        ]
                    ]
                ];
                break;
            case 'cancelFrb':
                return [
                    'freespinsid' => 456
                ];
                break;
            default:
                break;
                
        }
    }

    public function authorize($args, $generated_launch_url){
        $get_args = $this->urlParseVars($generated_launch_url);
        $this->sess_key = $get_args['ticket'];
        
        $arr = [
            'token'   => $this->sess_key,
            'channel' => 'web',
            'gameref' => $args['gid']
        ];
        
        return $this->_post($arr, '?action=verifyToken');
    }

    public function balance($args){
        $arr = [
            'cashiertoken' => $this->sess_key,
            'channel'      => 'web',
            'gameref'      => $args['gid']
        ];
        return $this->_post($arr, 'getBalance');
    }

    public function bet($args, $bet_id = null){
        $this->bet_id = $bet_id ?? rand(1000000, 10000000);
        $this->round_id = rand(1000000, 10000000);
         $arr = [
            'cashiertoken'  => $this->sess_key,
            'channel'       => 'web',
            'gameref'       => $args['gid'],
            'amount'        => $args['bet'],
            'customerid'    => $this->getUsrId($args['uid']),
            'txid'          => $this->bet_id,
            'gamesessionid' => $this->round_id
        ];

        return $this->_post($arr, 'withdraw');
    }

    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);
        $arr = [
            'cashiertoken'  => $this->sess_key,
            'channel'       => 'web',
            'gameref'       => $args['gid'],
            'amount'        => $args['win'],
            'customerid'    => $this->getUsrId($args['uid']),
            'txid'          => $this->win_id,
            'gamesessionid' => $this->round_id,
            'ended'         => $last_round,
            'txtype'        => 'deposit'
        ];

        if(!empty($this->frb_id)){
            $arr['txtype'] = 'freespinspayout';
            $arr['promocode'] = $this->frb_id;
        }
        
        return $this->_post($arr, 'deposit');
    }

    public function jpWin($args){
        $this->win_id = rand(1000000, 10000000);
          $arr = [
            'cashiertoken'  => $this->sess_key,
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
            'txid'         => uniqid(),
            'customerid'   => $this->getUsrId($args['uid']),
            'originaltxid' => $origin_id,
        ];

        return $this->_post($arr, 'rollback');
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
        //$this->bet($args, $this->bet_id);
        
        $this->win($args, null, true);
        // Idempotency test.
        //$this->win($args, $this->win_id);
    }
    
}
