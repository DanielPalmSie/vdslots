<?php

require_once 'TestGpr.php';

class TestIsoftbetGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'awardFrb':
                return [
                ];
                break;
            case 'cancelFrb':
                return '';
                break;
            default:
                break;
                
        }
    }

    public function authenticate($args){
        $arr = [
            "playerid" => $this->getUsrId($args['uid']),
            "skinid" => $args['gid'],
            "action" => [
                "command" => "init",
                'parameters' => [
                    'token' => $this->sess_key
                ]
            ]
        ];
        
        return $this->_post($arr, '', $args);
    }

    public function balance($args){
        $arr = [
            "playerid" => $this->getUsrId($args['uid']),
            "skinid" => $args['gid'],
            "action" => [
                "command" => "balance"
            ]
        ];
        
        return $this->_post($arr, '', $args);
    }

    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }

        $arr = [
            "playerid" => $this->getUsrId($args['uid']),
            "skinid" => $args['gid'],
            "action" => [
                "command" => "bet",
                'parameters' => [
                    'transactionid' => $this->bet_id,
                    'roundid' => $this->round_id,
                    'amount' => $args['bet']
                ]
            ]
        ];
        
        return $this->_post($arr, '', $args);
    }

    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $arr = [
            "playerid" => $this->getUsrId($args['uid']),
            "skinid" => $args['gid'],
            "action" => [
                "command" => "win",
                'parameters' => [
                    'transactionid' => $this->win_id,
                    'roundid' => $this->round_id,
                    'amount' => $args['win']
                ]
            ]
        ];
        
        return $this->_post($arr, '', $args);
    }

    public function rollback($args, $origin_id){
        $arr = [
            "playerid" => $this->getUsrId($args['uid']),
            "skinid" => $args['gid'],
            "action" => [
                "command" => "cancel",
                'parameters' => [
                    'transactionid' => $origin_id,
                    'roundid' => $this->round_id,
                ]
            ]
        ];
        
        return $this->_post($arr, '', $args);
    }

    public function launchUrl($args){
        $url = parent::launchUrl($args);
        $url_vars = $this->urlParseVars($url);
        $this->sess_key = $this->getToken($args, $url_vars['token']);
        return $url;
    }

    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        $this->authenticate($args);
        
        $this->balance($args);

        $this->bet($args);
        exit;
        
        // Idempotency test
        $this->bet($args, $this->bet_id);

        //$this->win($args, null, true);
        //exit;
        //$this->jpWin($args);
        //exit;
        // Idempotency test.
        //$this->win($args, $this->win_id);

        //$this->rollback($args, $origin_id ?? $this->bet_id);
        // Idempotency test.
        //$this->rollback($args, $origin_id ?? $this->bet_id);
    }
    
}
