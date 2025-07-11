<?php
require_once 'TestGpr.php';
class TestYggdrasilGpr extends TestGpr
{

    public function init($args){
        parent::init($args);
        $this->echo_res_body = true;
    }

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                    'data' => [
                        [
                            'prepaidTypeId' => 123,
                            'prepaidId' => 456
                        ]
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
    
    function authorize($args){
        $arr = [
            'sessiontoken' => $this->sess_key,
            'action' => 'playerinfo'
        ];
        
        return $this->_get($arr, '', $args);
    }

    function balance($args){
        $arr = [
            'sessiontoken' => $this->sess_key,
            'action' => 'getbalance'
        ];
        
        return $this->_get($arr, '', $args);
    }

    
    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }
        
        $arr = [
            'action'         => 'wager',
            'sessiontoken'   => $this->sess_key,
            'playerid'       => $this->getUsrId($args['uid']),
            'amount'         => $args['bet'],
            'reference'      => $this->round_id,
            'subreference'   => $bet_id ?? $this->bet_id,
        ];

        return $this->_get($arr, '', $args);        
    }

    function win($args, $win_id = null){
        if(!$win_id){
            $this->win_id = rand(1000000, 10000000);
        }

        $arr = [
            'tag3'          => 'Channel.desktop',
            'cat5'          => $args['gid'],
            'action'        => 'endwager',
            'sessiontoken'  => $this->sess_key,
            'reference'     => $this->round_id ?? uniqid(),
            'amount'        => $args['win'],
            'playerid'      => $this->getUsrId($args['uid']),
            'subreference'  => $win_id ?? $this->win_id
        ];

        if(!empty($this->frb_id)){
            $arr['action'] = 'campaignpayout';
            $arr['campaignref'] = $this->frb_id;
            $arr['prepaidref'] = $arr['reference'];
        }

        return $this->_get($arr, '', $args);        
    }

    // {"action":"cancelwager","org":"VideoSlots","playerid":"5229088_1","reference":"2402220409440100001","subreference":"w2402220409440100000","version":"3"}
    public function rollback($args, $bet_id){
        $arr = [
            'action'        => 'cancelwager',
            'playerid'      => $this->getUsrId($args['uid']),
            'subreference'  => $bet_id,
            'reference'     => $this->round_id,
        ];
        
        return $this->_get($arr, '', $args);
    }

    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        $this->sess_key = $this->urlParseVars($launch_url)['key'];
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args);
        
        $this->balance($args);
        
        $this->bet($args);
        
        $this->rollback($args, $this->bet_id);
        //$this->bet($args, $this->bet_id);
        exit;

        // Idempotency test
        //$this->bet($args, $this->bet_id);

        $this->win($args, null);
        exit;
        
        // Idempotency test.
        $this->win($args, $this->win_id);
    }

}
