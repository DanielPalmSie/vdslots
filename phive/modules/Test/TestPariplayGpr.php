<?php

require_once 'TestGpr.php';

class TestPariplayGpr extends TestGpr
{

    public function mockReply($from_gpr, $from_brand){
        switch($from_brand['action']){
            case 'login':
                return [
                    'Url' => 'http://abc.com',
                    'Token' => phive()->uuid()
                ];
                break;
            case 'awardFrb':
                return '';
                break;
            case 'cancelFrb':
                return '';
                break;
            default:
                break;
                
        }
    }

    public function authorize($args){
        $arr = [
            'Token' => $this->sess_key,
            'PlayerId'  => $this->getUsrId($args['uid']),
        ];
        return $this->_post($arr, 'Authenticate');
    }

    
    public function balance($args){
        $arr = [
            'Token' => $this->sess_key,
            'PlayerId'  => $this->getUsrId($args['uid']),
        ];
        return $this->_post($arr, 'GetBalance');
    }

    public function endRound($args){
        $arr = [
            'Token' => $this->sess_key,
            'PlayerId'  => $this->getUsrId($args['uid']),
            'RoundId' => $this->round_id,
            'GameCode' => $args['gid'],
        ];
        return $this->_post($arr, 'EndGame');
    }

    
    public function bet($args, $bet_id = null){
        $this->bet_id = $bet_id ?? rand(1000000, 10000000);
        $this->round_id = rand(1000000, 10000000);

        $arr = [
            'Token' => $this->sess_key,
            'PlayerId'  => $this->getUsrId($args['uid']),
            'RoundId' => $this->round_id,
            'TransactionId' => $this->bet_id,
            'Amount' => $args['bet'],
            'GameCode' => $args['gid'],
        ];
        return $this->_post($arr, 'Debit');
    }

    public function win($args, $win_id = null, $last_round = false, $jp_amount = 0){
        $this->win_id = $win_id ?? rand(1000000, 10000000);

        $arr = [
            'Token' => $this->sess_key,
            'PlayerId'  => $this->getUsrId($args['uid']),
            'RoundId' => $this->round_id,
            'TransactionId' => $this->win_id,
            'Amount' => $args['win'],
            'GameCode' => $args['gid'],
        ];

        if(!empty($jp_amount)){
            $arr['JackpotWinDetails']['Amount'] = $jp_amount; 
        }
        
        return $this->_post($arr, 'Credit');
    }

    
    public function betAndWin($args, $bet_id = null, $win_id = null){
        $this->bet_id = $bet_id ?? rand(1000000, 10000000);
        $this->round_id = rand(1000000, 10000000);

        $arr = [
            'Token' => $this->sess_key,
            'PlayerId'  => $this->getUsrId($args['uid']),
            'RoundId' => $this->round_id,
            'TransactionId' => $this->bet_id,
            'DebitAmount' => $args['bet'],
            'CreditAmount' => $args['win'],
            'GameCode' => $args['gid'],
        ];
        return $this->_post($arr, 'DebitAndCredit');
    }
    
    public function jpWin($args){
        $jp_win = '100.000000';
        $args['win'] += $jp_win;
        return $this->win($args, null, true, $jp_win);
    }

    public function rollback($args, $origin_id = null){
        $origin_id = $origin_id ?? $this->bet_id;
        $arr = [
            'PlayerId' => $this->getUsrId($args['uid']),
            'RefTransactionId' => $origin_id,
            'GameCode' => $args['gid'],
            'CancelEntireRound' => false,
            'TransactionId' => uniqid()
        ];

        return $this->_post($arr, 'CancelTransaction');
    }
    
    public function rollbackBetAndWin($args, $bet_id = null, $win_id = null){
        $arr = [
            'PlayerId' => $this->getUsrId($args['uid']),
            'GameCode' => $args['gid'],
            'CancelEntireRound' => true,
            'RoundId' => $this->round_id,
            'TransactionId' => uniqid()
        ];
        
        return $this->_post($arr, 'CancelTransaction');
    }

    public function doLaunch($args, $game_key = null){
        $launch_url = parent::doLaunch($args, $game_key);
        $this->sess_key = $this->gpr->token;
        return $launch_url;
    }

    public function doFullRun($args, $origin_id = null){
        $launch_url = $this->doLaunch($args);
        echo "\nLaunch URL: $launch_url \n\n";
        
        $this->authorize($args);
        $this->balance($args);
        $this->bet($args);
        $this->endRound($args);
        $this->jpWin($args);
        //exit;

        $this->betAndWin($args);
        $this->jpWin($args);
        $this->rollbackBetAndWin($args, $this->bet_id, $this->win_id);
        exit;
        
        $this->rollback($args, $this->bet_id);
        $this->win($args);
        exit;

        //exit;

        
        //exit;
        
        // Idempotency test
        $this->bet($args, $this->bet_id);
        
        // Idempotency test.
        
    }
    
}
