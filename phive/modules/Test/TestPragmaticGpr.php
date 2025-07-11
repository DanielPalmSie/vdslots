<?php

require_once 'TestGpr.php';

class TestPragmaticGpr extends TestGpr
{

    public function init($args){
        parent::init($args);
        $this->http_data_type = 'form';
    }

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'login':
                $data = urlencode(http_build_query($from_gpr));
                return [
                    'error' => 0,
                    'description' => 'OK',
                    "gameURL" => "https://test1.prerelease-env.biz/gs2c/playGame.do?key=$data",
                ];
                break;
            case 'awardFrb':
                return [
                    'error' => 0,
                    'description' => 'OK',
                ];
                break;
            case 'cancelFrb':
                return [
                    'error' => 0,
                    'description' => 'OK',
                ];
                break;
            default:
                break;
                
        }
    }

    
    public function authorize($args, $generated_launch_url){
        $this->sess_key = $this->getToken($args, $this->gpr->token);

        $arr = [
            'token' => $this->sess_key
        ];
        
        return $this->_post($arr, 'authenticate.html', $args);
    }

    public function balance($args){
        $arr = [
            'token' => $this->sess_key,
            'userId' => $this->getUsrId($args['uid'])
        ];
        
        return $this->_post($arr, 'balance.html', $args);
    }

    public function bet($args, $bet_id = null){
        if(!$bet_id){
            $this->bet_id = rand(1000000, 10000000);
            $this->round_id = rand(1000000, 10000000);
        }
        
        $arr = [
            'token'     => $this->sess_key,
            'userId'    => $this->getUsrId($args['uid']),
            'currency'  => $args['currency'],
            'amount'    => $args['bet'],
            'roundId'   => $this->round_id,
            'reference' => $this->bet_id,
            'gameId'    => $args['gid']
        ];

        return $this->_post($arr, 'bet.html', $args);
    }

    public function win($args, $win_id = null, $last_round = false){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $arr = [
            'token'     => $this->sess_key,
            'userId'    => $this->getUsrId($args['uid']),
            'currency'  => $args['currency'],
            'amount'    => $args['bet'],
            'roundId'   => $this->round_id,
            'reference' => $this->win_id,
            'gameId'    => $args['gid']
        ];
        
        return $this->_post($arr, 'result.html', $args);
    }

    public function frbWin($args, $entry){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        //echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $win_id = rand(1000000, 10000000);

        $arr = [
            'token'     => $this->sess_key,
            'userId'    => $this->getUsrId($args['uid']),
            'currency'  => $args['currency'],
            'amount'    => $args['bet'],
            'roundId'   => $this->round_id,
            'reference' => $this->win_id,
            'gameId'    => $args['gid'],
            'bonusCode' => $entry['id']
        ];

        return $this->_post($arr, 'result.html', $args);
    }

    public function jpWin($args){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        //echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $win_id = rand(1000000, 10000000);

        $arr = [
            'token'     => $this->sess_key,
            'userId'    => $this->getUsrId($args['uid']),
            'currency'  => $args['currency'],
            'amount'    => $args['bet'],
            'roundId'   => $this->round_id,
            'reference' => $win_id,
            'gameId'    => $args['gid'],
            'jackpotId' => 1234 
        ];

        return $this->_post($arr, 'result.html', $args);
    }

    public function rollback($args, $origin_id){
        $arr = [
            'token'     => $this->sess_key,
            'userId'    => $this->getUsrId($args['uid']),
            'currency'  => $args['currency'],
            'amount'    => $args['bet'],
            'roundId'   => $this->round_id,
            'reference' => $this->bet_id,
            'gameId'    => $args['gid']
        ];
        
        return $this->_post($arr, 'refund.html', $args);
    }

    public function setupFrb(){
        $bonus = phive('SQL')->loadAssoc("SELECT * FROM bonus_types WHERE id = 2054");
        $bonus['bonus_tag'] = 'evolution';
        $bonus['game_id'] = 'evolution_starburstr300000';
        $bonus['ext_ids'] = 'MT:MTcampid|GB:GBcampid|CA-ON:ONcampid';
        phive('SQL')->save('bonus_types', $bonus);
    }
    
    public function doFullRun($args, $origin_id = null){
        $this->setupAjaxInitGameSession($args);
        $launch_url = $this->launchUrl($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args, $launch_url);
        
        $this->balance($args);

        $this->bet($args);
        
        //$this->rollback($args, $origin_id ?? $this->bet_id);
        //exit;

        // Idempotency test
        $this->bet($args, $this->bet_id);

        $this->win($args, null, true);
        // Idempotency test.
        $this->win($args, $this->win_id);
        
    }
    
}
